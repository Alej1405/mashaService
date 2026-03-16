<?php

namespace App\Filament\App\Widgets;

use App\Models\SaleItem;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class TopProductosWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.top-productos';
    
    protected int | string | array $columnSpan = [
        'default' => 1,
        'sm'      => 1,
        'md'      => 1,
        'lg'      => 1,
        'xl'      => 1,
    ];

    protected static ?int $sort = 5;

    public string $periodo;

    public function mount()
    {
        $this->periodo = session('dashboard_periodo', 'mes');
    }

    #[On('dashboard-periodo-updated')]
    public function updatePeriodo($periodo)
    {
        $this->periodo = $periodo;
    }

    protected function getViewData(): array
    {
        $tenantId = Filament::getTenant()->id;
        [$desde, $hasta] = $this->getFechas($this->periodo);

        $top = SaleItem::query()
            ->select(
                'inventory_item_id',
                DB::raw('SUM(cantidad) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->whereHas('sale', function($q) use ($tenantId, $desde, $hasta) {
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
                'name' => $item->inventoryItem->nombre ?? 'Producto Eliminado',
                'qty' => $item->total_qty,
                'revenue' => $item->total_revenue,
                'percent' => ($item->total_qty / $maxQty) * 100
            ]),
        ];
    }

    protected function getFechas($p): array
    {
        return match($p) {
            'hoy'     => [today(), today()],
            'semana'  => [now()->startOfWeek(), now()->endOfWeek()],
            'mes'     => [now()->startOfMonth(), now()->endOfMonth()],
            'año'     => [now()->startOfYear(), now()->endOfYear()],
            default   => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
