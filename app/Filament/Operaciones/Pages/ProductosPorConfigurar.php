<?php

namespace App\Filament\Operaciones\Pages;

use App\Filament\Operaciones\Resources\InventarioResource;
use App\Models\InventoryItem;
use App\Models\ProductoProveedor;
use App\Models\TipoGasto;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Modules\Compras\Queries\ComprasElectronicasPendientes;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

/**
 * Productos que un proveedor facturó y el inventario no conoce. Una tarea: decir
 * qué es cada uno (un ítem del inventario o un gasto). Se dice una vez: las
 * compras que esperaban se registran y las siguientes de ese producto entran solas.
 */
class ProductosPorConfigurar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Productos por configurar';
    protected static ?string $title           = 'Productos por configurar';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.operaciones.productos-por-configurar';

    /** producto_proveedor_id => 'item:12' | 'gasto:5' */
    public array $eleccion = [];

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('inventario');
    }

    public static function getNavigationBadge(): ?string
    {
        $empresa = Filament::getTenant();
        $n = $empresa ? app(ComprasElectronicasPendientes::class)->productos($empresa->id)->count() : 0;

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function guardar(int $id): void
    {
        $producto = app(ComprasElectronicasPendientes::class)->productos(Filament::getTenant()->id)->findOrFail($id);
        [$tipo, $valor] = array_pad(explode(':', (string) ($this->eleccion[$id] ?? '')), 2, null);

        if ($tipo === 'item' && ! InventoryItem::where('empresa_id', $producto->empresa_id)->whereKey($valor)->exists()) {
            $tipo = null;
        }

        try {
            $confirmadas = app(ConfirmarCompraElectronica::class)->producto(
                $producto,
                $tipo === 'item' ? (int) $valor : null,
                $tipo === 'gasto' ? (int) $valor : null,
                auth()->id(),
            );
        } catch (ValidationException $e) {
            Notification::make()->title('Falta un dato')->body(collect($e->errors())->flatten()->first())->warning()->send();

            return;
        }

        unset($this->eleccion[$id]);
        Notification::make()->title('Producto configurado')
            ->body($confirmadas > 0
                ? "{$confirmadas} " . ($confirmadas === 1 ? 'compra quedó registrada' : 'compras quedaron registradas') . ' con su entrada al inventario.'
                : 'Las próximas facturas de este producto entran solas.')
            ->success()->send();
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();

        $productos = app(ComprasElectronicasPendientes::class)->productos($empresa->id)
            ->with('supplier:id,nombre')
            ->withCount(['lineas as esperando' => fn ($q) => $q->whereHas('purchase', fn ($p) => $p->where('status', 'borrador'))])
            ->orderByDesc('updated_at')->limit(150)->get();

        return [
            'productos'  => $productos,
            'items'      => InventoryItem::where('empresa_id', $empresa->id)->where('activo', true)
                ->orderBy('type')->orderBy('nombre')->get()->groupBy('type')->map(fn ($g) => $g->pluck('nombre', 'id')),
            'tiposGasto' => TipoGasto::where('activo', true)
                ->where(fn ($q) => $q->whereNull('empresa_id')->orWhere('empresa_id', $empresa->id))
                ->orderBy('nombre')->pluck('nombre', 'id'),
            'urlInventario' => InventarioResource::getUrl('index', panel: 'operaciones', tenant: $empresa),
        ];
    }
}
