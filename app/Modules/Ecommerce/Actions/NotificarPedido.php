<?php

namespace App\Modules\Ecommerce\Actions;

use App\Models\StoreOrder;
use App\Modules\Soporte\Queries\DestinatariosSoporte;
use App\Shared\Attributes\Documentado;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Avisa a los usuarios de la empresa que entró un pedido: en la campana del ERP
 * y por Telegram (vía el flujo de avisos de n8n). Telegram sale después de
 * responder: si n8n tarda, el pedido se guarda igual.
 */
#[Documentado(
    grupo: 'Tienda',
    descripcion: 'Avisa un pedido nuevo en la campana del ERP y por Telegram, con su orden de producción.',
    tipo: 'action',
)]
final class NotificarPedido
{
    public function __construct(private readonly DestinatariosSoporte $destinatarios) {}

    /** @param Collection<int,\App\Models\ProductionOrder> $ordenes */
    public function nuevo(StoreOrder $order, Collection $ordenes, array $sinDiseno = []): void
    {
        $usuarios = $this->destinatarios->empresa($order->empresa_id);

        if ($usuarios->isEmpty()) {
            return;
        }

        $cliente = $order->customer?->nombre_completo ?? 'Cliente';
        $total = '$' . number_format((float) $order->total, 2);

        Notification::make()->title("Nuevo pedido · {$cliente}")
            ->body("{$order->numero} por {$total}. Orden de producción: " . ($ordenes->pluck('referencia')->join(', ') ?: 'ninguna') . '.')
            ->icon('heroicon-o-shopping-bag')->iconColor('success')
            ->sendToDatabase($usuarios);

        $this->telegram($order, $this->destinatarios->chats($usuarios), $this->texto($order, $ordenes, $sinDiseno));
    }

    private function texto(StoreOrder $order, Collection $ordenes, array $sinDiseno): string
    {
        $m = fn ($n) => '$' . number_format((float) $n, 2);
        $lineas = $order->orderItems->map(fn ($i) => '• ' . Str::limit((string) $i->nombre_snapshot, 40)
            . ' × ' . rtrim(rtrim(number_format((float) $i->cantidad, 4, '.', ''), '0'), '.')
            . ' = ' . $m($i->subtotal)
            . ($i->nota ? "\n   📝 " . Str::limit($i->nota, 200) : ''))->implode("\n");

        $texto = "🛒 Nuevo pedido {$order->numero}\n"
            . ($order->customer?->nombre_completo ?? 'Cliente') . "\n\n"
            . $lineas . "\n\n"
            . "Total {$m($order->total)}\n\n"
            . '🏭 Orden de producción: ' . ($ordenes->pluck('referencia')->join(', ') ?: 'ninguna');

        if ($sinDiseno) {
            $texto .= "\n⚠️ Sin diseño de producto, no se pudo programar: " . implode(', ', $sinDiseno) . '.';
        }

        if ($order->notas_cliente) {
            $texto .= "\n\n📝 " . Str::limit((string) $order->notas_cliente, 300);
        }

        return $texto;
    }

    private function telegram(StoreOrder $order, array $chats, string $texto): void
    {
        $url = config('services.n8n.webhook_compras');

        if (! $url || empty($chats)) {
            return;
        }

        $payload = ['evento' => 'pedido_recibido', 'destinatarios' => $chats, 'texto' => $texto, 'botones' => []];
        $secreto = (string) config('n8n.secret');
        $numero = $order->numero;

        dispatch(function () use ($url, $secreto, $payload, $numero) {
            try {
                Http::timeout(5)->withHeaders(['X-N8N-Secret' => $secreto])->post($url, $payload);
            } catch (\Throwable $e) {
                Log::warning('No se pudo avisar a n8n de un pedido nuevo', ['pedido' => $numero, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }
}
