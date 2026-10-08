<?php

namespace App\Filament\Contabilidad\Pages;

use App\Filament\Operaciones\Pages\ProductosPorConfigurar;
use App\Models\Purchase;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Modules\Compras\Actions\ResolverFormaPago;
use App\Modules\Compras\Queries\ComprasElectronicasPendientes;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

/**
 * Facturas de compra que llegaron por correo o Telegram y no se pudieron
 * registrar solas. Una tarea: decir cómo se pagó cada una. Si además falta
 * configurar un producto, eso se hace en Inventario y aquí solo se avisa.
 */
class ComprasPorCompletar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Compras por completar';
    protected static ?string $navigationGroup = 'Día a día';
    protected static ?string $title           = 'Compras por completar';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.contabilidad.compras-por-completar';

    /** compra_id => ['pago' => 'efectivo:3' | 'credito:0', 'plazo' => 30] */
    public array $eleccion = [];

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function getNavigationBadge(): ?string
    {
        $empresa = Filament::getTenant();
        $n = $empresa ? app(ComprasElectronicasPendientes::class)->compras($empresa->id)->count() : 0;

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function registrar(int $id): void
    {
        $compra = app(ComprasElectronicasPendientes::class)->compras(Filament::getTenant()->id)->findOrFail($id);
        [$forma, $medio] = array_pad(explode(':', (string) ($this->eleccion[$id]['pago'] ?? '')), 2, null);

        try {
            $listo = $compra->requiere_forma_pago
                ? app(ConfirmarCompraElectronica::class)->formaPago($compra, (string) $forma, (int) $medio ?: null, (int) ($this->eleccion[$id]['plazo'] ?? 0) ?: null)
                : app(ConfirmarCompraElectronica::class)->intentar($compra);
        } catch (ValidationException $e) {
            Notification::make()->title('Falta un dato')->body(collect($e->errors())->flatten()->first())->warning()->send();

            return;
        }

        $compra->refresh();
        Notification::make()
            ->title($listo ? 'Compra registrada' : 'Forma de pago guardada')
            ->body($listo
                ? "Factura {$compra->numero_factura}: asiento generado y el inventario al día."
                : ($compra->error_contable_msg ?: 'Falta configurar sus productos en Inventario para registrarla.'))
            ->{$listo ? 'success' : 'warning'}()
            ->send();
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $formas = app(ResolverFormaPago::class);

        $opciones = [];
        foreach (['efectivo', 'transferencia', 'tarjeta'] as $forma) {
            foreach ($formas->medios($empresa->id, $forma) as $id => $nombre) {
                $opciones["{$forma}:{$id}"] = ResolverFormaPago::ETIQUETAS[$forma] . ' · ' . $nombre;
            }
        }
        $opciones['credito:0'] = ResolverFormaPago::ETIQUETAS['credito'];

        $compras = app(ComprasElectronicasPendientes::class)->compras($empresa->id)
            ->with('supplier:id,nombre')->withCount('itemsPorConfigurar')
            ->orderBy('date')->limit(100)->get();

        foreach ($compras as $c) {
            $this->eleccion[$c->id] ??= ['pago' => '', 'plazo' => $c->plazo_dias ?: 30];
        }

        return [
            'compras'      => $compras,
            'opciones'     => $opciones,
            'urlProductos' => ProductosPorConfigurar::getUrl(panel: 'operaciones', tenant: $empresa),
        ];
    }
}
