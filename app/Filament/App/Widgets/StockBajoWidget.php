<?php

namespace App\Filament\App\Widgets;

use App\Models\InventoryItem;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class StockBajoWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.stock-bajo';
    
    protected int | string | array $columnSpan = [
        'default' => 1,
        'sm'      => 1,
        'md'      => 1,
        'lg'      => 1,
        'xl'      => 1,
    ];

    protected static ?int $sort = 4;

    protected function getViewData(): array
    {
        $tenantId = Filament::getTenant()->id;
        
        $productos = InventoryItem::where('empresa_id', $tenantId)
            ->where(function($q) {
                // stock_actual <= stock_minimo * 1.2
                $q->whereRaw('stock_actual <= (stock_minimo * 1.2)')
                  ->orWhere('stock_actual', '<=', 0);
            })
            ->orderBy('stock_actual', 'asc')
            ->limit(8)
            ->get()
            ->map(function($item) {
                $estado = 'bajo';
                if ($item->stock_actual <= 0) $estado = 'agotado';
                elseif ($item->stock_actual <= $item->stock_minimo) $estado = 'critico';

                return [
                    'name' => $item->nombre,
                    'stock' => $item->stock_actual,
                    'min' => $item->stock_minimo,
                    'estado' => $estado,
                ];
            });

        return [
            'productos' => $productos,
            'tenant' => Filament::getTenant()->slug,
        ];
    }
}
