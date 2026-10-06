<?php

namespace App\Filament\App\Widgets;

use App\Models\Sale;
use App\Models\Purchase;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class VentasComprasWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.ventas-compras';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'sm'      => 2,
        'md'      => 1,
        'lg'      => 1,
        'xl'      => 1,
    ];

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    protected function getViewData(): array
    {
        $tenantId = Filament::getTenant()->id;
        $desde    = now()->subMonths(5)->startOfMonth();
        $hasta    = now()->endOfMonth();
        $desdeStr = $desde->toDateString();
        $hastaStr = $hasta->toDateString();

        // 2 queries totales en lugar de 2 × 6 meses = 12
        $ventas = Sale::where('empresa_id', $tenantId)
            ->where('estado', 'confirmado')
            ->whereBetween('fecha', [$desdeStr, $hastaStr])
            ->get(['fecha', 'total'])
            ->groupBy(fn($r) => Carbon::parse($r->fecha)->format('Y-m'))
            ->map(fn($rows) => $rows->sum('total'));


        $compras = Purchase::where('empresa_id', $tenantId)
            ->where('status', 'confirmado')
            ->whereBetween('date', [$desdeStr, $hastaStr])
            ->get(['date', 'total'])
            ->groupBy(fn($r) => Carbon::parse($r->date)->format('Y-m'))
            ->map(fn($rows) => $rows->sum('total'));


        $meses = $ventasData = $comprasData = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha  = now()->subMonths($i);
            $key    = $fecha->format('Y-m');
            $meses[]       = ucfirst($fecha->translatedFormat('M'));
            $ventasData[]  = (float) ($ventas[$key] ?? 0);
            $comprasData[] = (float) ($compras[$key] ?? 0);
        }

        return [
            'labels'  => $meses,
            'ventas'  => $ventasData,
            'compras' => $comprasData,
        ];
    }
}
