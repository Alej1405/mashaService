<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\CustomerResource;
use App\Filament\App\Resources\InventoryItemResource;
use App\Filament\App\Resources\StoreProductResource;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\StoreProduct;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Storage;

/**
 * Dashboard operativo (Pro/Enterprise). Vista propia diseñada — mismo patrón que
 * los dashboards de Ecommerce/CMS/hub (Page + Blade + getViewData), no un grid de
 * widgets sueltos.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title          = 'Inicio';
    protected static ?int    $navigationSort  = -2;

    protected static string $view = 'filament.app.pages.dashboard';

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $eid     = $empresa->id;
        $user    = auth()->user();

        $hora   = now()->hour;
        $saludo = match (true) {
            $hora >= 6 && $hora < 12  => 'Buenos días',
            $hora >= 12 && $hora < 19 => 'Buenas tardes',
            default                    => 'Buenas noches',
        };

        $productosPub   = StoreProduct::withoutGlobalScopes()->where('empresa_id', $eid)->where('publicado', true)->count();
        $productosTotal = StoreProduct::withoutGlobalScopes()->where('empresa_id', $eid)->count();
        $insumos        = InventoryItem::withoutGlobalScopes()->where('empresa_id', $eid)
            ->whereIn('type', ['insumo', 'materia_prima'])->where('activo', true)->count();
        $clientes       = Customer::withoutGlobalScopes()->where('empresa_id', $eid)->count();

        $stockBajo = InventoryItem::withoutGlobalScopes()->where('empresa_id', $eid)->where('activo', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->with('measurementUnit')
            ->orderBy('stock_actual')
            ->limit(6)
            ->get();

        $alertas = InventoryItem::withoutGlobalScopes()->where('empresa_id', $eid)->where('activo', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        return [
            'empresa'        => $empresa,
            'saludo'         => $saludo . ', ' . explode(' ', $user->name)[0],
            'fecha'          => ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')),
            'inicial'        => mb_strtoupper(mb_substr($empresa->name ?? '?', 0, 1)),
            'logo'           => ($lp = $empresa->logo_path) && Storage::disk('public')->exists($lp)
                                    ? asset('storage/' . ltrim($lp, '/')) : null,
            'plan'           => $empresa->servicePlan?->nombre ?? ucfirst($empresa->plan ?? 'pro'),
            'productosPub'   => $productosPub,
            'productosTotal' => $productosTotal,
            'insumos'        => $insumos,
            'clientes'       => $clientes,
            'alertas'        => $alertas,
            'stockBajo'      => $stockBajo,
            'urlProductos'   => StoreProductResource::getUrl('index'),
            'urlInventario'  => InventoryItemResource::getUrl('index'),
            'urlClientes'    => CustomerResource::getUrl('index'),
        ];
    }
}
