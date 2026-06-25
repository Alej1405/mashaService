<?php

namespace App\Filament\Ecommerce\Pages;

use App\Models\Customer;
use App\Models\StoreCategory;
use App\Models\StoreCoupon;
use App\Models\StoreOrder;
use App\Models\StoreProduct;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class EcommerceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title          = 'Dashboard Tienda';
    protected static ?string $slug           = 'dashboard';
    protected static ?int    $navigationSort = -1;

    protected static string $view = 'filament.ecommerce.pages.ecommerce-dashboard';

    public function getTitle(): string
    {
        $empresa = Filament::getTenant();
        return 'Tienda — ' . ($empresa?->name ?? 'E-Commerce');
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $eid     = $empresa->id;
        $now     = now();

        $recentOrders = StoreOrder::where('empresa_id', $eid)
            ->with('customer')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'empresa'           => $empresa,
            'pendingOrders'     => StoreOrder::where('empresa_id', $eid)->where('estado', 'pendiente')->count(),
            'monthRevenue'      => (float) StoreOrder::where('empresa_id', $eid)
                                        ->whereMonth('created_at', $now->month)
                                        ->whereYear('created_at', $now->year)
                                        ->where('estado_pago', 'aprobado')
                                        ->sum('total'),
            'customersCount'    => Customer::where('empresa_id', $eid)->count(),
            'productsPublished' => StoreProduct::where('empresa_id', $eid)->where('publicado', true)->count(),
            'productsTotal'     => StoreProduct::where('empresa_id', $eid)->count(),
            'categoriesCount'   => StoreCategory::where('empresa_id', $eid)->count(),
            'couponsActive'     => StoreCoupon::where('empresa_id', $eid)->where('activo', true)->count(),
            'ordersTotal'       => StoreOrder::where('empresa_id', $eid)->count(),
            'recentOrders'      => $recentOrders,
        ];
    }
}
