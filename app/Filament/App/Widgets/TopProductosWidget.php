<?php

namespace App\Filament\App\Widgets;

use App\Models\SaleItem;
use App\Traits\HasDashboardPeriodo;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopProductosWidget extends Widget
{
    use HasDashboardPeriodo;

    protected static string $view = 'filament.app.widgets.top-productos';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'sm'      => 1,
        'md'      => 1,
        'lg'      => 1,
        'xl'      => 1,
    ];

    protected static ?int $sort = 6;

    protected function getViewData(): array
    {
        $tenantId = Filament::getTenant()->id;
        [$desde, $hasta] = $this->getFechas($this->periodo);

        $top = SaleItem::query()
            ->select('inventory_item_id', DB::raw('SUM(cantidad) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->whereHas('sale', function ($q) use ($tenantId, $desde, $hasta) {
                $q->where('empresa_id', $tenantId)
                  ->where('estado', 'confirmado')
                  ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
            })
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('inventoryItem')
            ->get();

        $maxQty = $top->max('total_qty') ?: 1;

        return [
            'productos' => $top->map(fn($item) => [
                'name'    => $item->inventoryItem->nombre ?? 'Producto Eliminado',
                'qty'     => $item->total_qty,
                'revenue' => $item->total_revenue,
                'percent' => ($item->total_qty / $maxQty) * 100,
            ]),
        ];
    }
}
