<?php

namespace App\Modules\Compras\Actions;

use App\Filament\Contabilidad\Pages\ComprasPorCompletar;
use App\Filament\Operaciones\Pages\ProductosPorConfigurar;
use App\Models\Purchase;
use App\Models\User;
use App\Modules\Soporte\Queries\DestinatariosSoporte;
use App\Shared\Attributes\Documentado;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Avisa que llegó una factura de compra, por los dos canales de siempre:
 * - la campana del ERP: en Contabilidad si falta la forma de pago, en Inventario
 *   si hay productos por configurar;
 * - Telegram (vía n8n), con el detalle y, si falta, la pregunta de cómo se pagó
 *   con un botón por cada caja, banco o tarjeta de la empresa.
 * Telegram sale después de responder: si n8n tarda, la compra se guarda igual.
 */
#[Documentado(
    grupo: 'Compras',
    descripcion: 'Avisa las facturas de compra recibidas en la campana del ERP y por Telegram.',
    tipo: 'action',
)]
final class NotificarCompra
{
    public function __construct(
        private readonly DestinatariosSoporte $destinatarios,
        private readonly ResolverFormaPago $formas,
    ) {}

    public function recibida(Purchase $compra): void
    {
        $compra->loadMissing('empresa', 'supplier', 'items.productoProveedor');
        $usuarios = $this->destinatarios->empresa($compra->empresa_id);
        if ($usuarios->isEmpty()) {
            return;
        }

        $this->campana($compra, $usuarios);
        $this->telegram($compra, $this->destinatarios->chats($usuarios));
    }

    /** El mensaje de Telegram, que también devuelve la API a n8n. */
    public function resumen(Purchase $compra): string
    {
        $compra->loadMissing('supplier', 'items.productoProveedor', 'cashRegister', 'bankAccount', 'creditCard');
        $m = fn ($n) => '$' . number_format((float) $n, 2);

        $lineas = $compra->items->take(8)->map(fn ($i) => '• ' . Str::limit((string) $i->descripcion, 40)
            . ' × ' . rtrim(rtrim(number_format((float) $i->quantity, 4, '.', ''), '0'), '.')
            . ' = ' . $m($i->total_item))->implode("\n");
        if ($compra->items->count() > 8) {
            $lineas .= "\n• … y " . ($compra->items->count() - 8) . ' líneas más';
        }

        $texto = "🧾 Factura de compra recibida\n"
            . "{$compra->supplier?->nombre}\n"
            . "N.º {$compra->numero_factura} · {$compra->date->format('d/m/Y')}\n\n"
            . $lineas . "\n\n"
            . "Subtotal {$m($compra->subtotal)} · IVA {$m($compra->iva)}\n"
            . "Total {$m($compra->total)}\n\n";

        $porConfigurar = $this->porConfigurar($compra);

        $texto .= match (true) {
            $compra->status === Purchase::CONFIRMADO => '✅ Registrada: ' . $this->comoSePago($compra) . '. Inventario y asiento al día.',
            $compra->requiere_forma_pago            => '❓ ¿Cómo se pagó? Elige abajo o complétalo en Contabilidad › Compras por completar.',
            $porConfigurar->isNotEmpty()            => '💳 Pago: ' . $this->comoSePago($compra) . '.',
            default                                 => '⚠️ ' . ($compra->error_contable_msg ?: 'Quedó en borrador.'),
        };

        if ($porConfigurar->isNotEmpty()) {
            $texto .= "\n📦 Productos nuevos por configurar en Inventario: " . $porConfigurar->implode(', ') . '.';
        }

        return $texto;
    }

    /**
     * Un botón por cada medio de pago activo, más crédito.
     *
     * @return array<int,array{texto: string, dato: string}>
     */
    public function opcionesDePago(Purchase $compra): array
    {
        $botones = [];
        foreach (['efectivo', 'transferencia', 'tarjeta'] as $forma) {
            foreach ($this->formas->medios($compra->empresa_id, $forma) as $id => $nombre) {
                $botones[] = ['texto' => ResolverFormaPago::ETIQUETAS[$forma] . ' · ' . Str::limit($nombre, 24), 'dato' => "cp:{$compra->id}:{$forma}:{$id}"];
            }
        }
        $botones[] = ['texto' => 'Crédito (por pagar)', 'dato' => "cp:{$compra->id}:credito:0"];

        return $botones;
    }

    /** @param Collection<int,User> $usuarios */
    private function campana(Purchase $compra, Collection $usuarios): void
    {
        $proveedor = $compra->supplier?->nombre;
        $total = '$' . number_format((float) $compra->total, 2);

        if ($compra->status === Purchase::CONFIRMADO) {
            Notification::make()->title("Compra registrada · {$proveedor}")
                ->body("Factura {$compra->numero_factura} por {$total}: " . $this->comoSePago($compra) . '.')
                ->icon('heroicon-o-shopping-cart')->iconColor('success')
                ->sendToDatabase($usuarios);
        }

        if ($compra->requiere_forma_pago || ($compra->error_contable_msg && $compra->status !== Purchase::CONFIRMADO)) {
            Notification::make()->title("Compra por completar · {$proveedor}")
                ->body("Factura {$compra->numero_factura} por {$total}: "
                    . ($compra->requiere_forma_pago ? 'falta decir cómo se pagó.' : $compra->error_contable_msg))
                ->icon('heroicon-o-banknotes')->iconColor('warning')
                ->actions([Action::make('completar')->label('Completar')->markAsRead()
                    ->url(ComprasPorCompletar::getUrl(panel: 'contabilidad', tenant: $compra->empresa))])
                ->sendToDatabase($usuarios);
        }

        $porConfigurar = $this->porConfigurar($compra);
        if ($porConfigurar->isNotEmpty()) {
            Notification::make()->title('Productos por configurar · ' . $porConfigurar->count())
                ->body("{$proveedor} facturó productos que el inventario no conoce: " . $porConfigurar->implode(', ') . '.')
                ->icon('heroicon-o-cube')->iconColor('warning')
                ->actions([Action::make('configurar')->label('Configurar')->markAsRead()
                    ->url(ProductosPorConfigurar::getUrl(panel: 'operaciones', tenant: $compra->empresa))])
                ->sendToDatabase($usuarios);
        }
    }

    /** @param array<int,string> $chats */
    private function telegram(Purchase $compra, array $chats): void
    {
        $url = config('services.n8n.webhook_compras');
        if (! $url || empty($chats)) {
            return;
        }

        $payload = [
            'evento'        => 'compra_recibida',
            'destinatarios' => $chats,
            'texto'         => $this->resumen($compra),
            'botones'       => $compra->requiere_forma_pago ? $this->opcionesDePago($compra) : [],
            'compra'        => ['id' => $compra->id, 'empresa' => $compra->empresa?->name, 'estado' => $compra->status],
        ];
        $secreto = (string) config('n8n.secret');

        dispatch(function () use ($url, $secreto, $payload) {
            try {
                Http::timeout(5)->withHeaders(['X-N8N-Secret' => $secreto])->post($url, $payload);
            } catch (\Throwable $e) {
                Log::warning('No se pudo avisar a n8n de una compra recibida', [
                    'compra_id' => $payload['compra']['id'], 'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    /** @return Collection<int,string> descripciones de las líneas que nadie configuró */
    private function porConfigurar(Purchase $compra): Collection
    {
        return $compra->itemsPorConfigurar()->pluck('descripcion')->map(fn ($d) => Str::limit((string) $d, 40))->unique()->values();
    }

    private function comoSePago(Purchase $compra): string
    {
        $medio = $compra->cashRegister?->nombre ?? $compra->creditCard?->nombre ?? $compra->bankAccount?->numero_cuenta;
        $forma = ResolverFormaPago::ETIQUETAS[$compra->forma_pago] ?? $compra->forma_pago;

        return match (true) {
            $compra->forma_pago === 'credito' => "a crédito, vence el {$compra->fecha_vencimiento?->format('d/m/Y')}",
            (bool) $medio                     => "{$forma} ({$medio})",
            default                           => $forma,
        };
    }
}
