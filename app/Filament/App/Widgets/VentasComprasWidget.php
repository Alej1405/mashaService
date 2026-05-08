<?php

namespace App\Filament\App\Widgets;

use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsShipmentBill;
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

        // 4 queries totales en lugar de 4 × 6 meses = 24
        $ventas = Sale::where('empresa_id', $tenantId)
            ->where('estado', 'confirmado')
            ->whereBetween('fecha', [$desdeStr, $hastaStr])
            ->get(['fecha', 'total'])
            ->groupBy(fn($r) => Carbon::parse($r->fecha)->format('Y-m'))
            ->map(fn($rows) => $rows->sum('total'));

        $logisticsIn = LogisticsBillingRequest::withoutGlobalScopes()
            ->where('empresa_id', $tenantId)
            ->whereIn('estado', ['facturado', 'cobrado'])
            ->whereNull('sale_id')
            ->whereBetween('updated_at', [$desdeStr . ' 00:00:00', $hastaStr . ' 23:59:59'])
            ->get(['updated_at', 'total'])
            ->groupBy(fn($r) => Carbon::parse($r->updated_at)->format('Y-m'))
            ->map(fn($rows) => $rows->sum('total'));

        $compras = Purchase::where('empresa_id', $tenantId)
            ->where('status', 'confirmado')
            ->whereBetween('date', [$desdeStr, $hastaStr])
            ->get(['date', 'total'])
            ->groupBy(fn($r) => Carbon::parse($r->date)->format('Y-m'))
            ->map(fn($rows) => $rows->sum('total'));

        $logisticsOut = LogisticsShipmentBill::withoutGlobalScopes()
            ->where('empresa_id', $tenantId)
            ->where('estado', 'pagada')
            ->whereBetween('fecha_pago', [$desdeStr, $hastaStr])
            ->get(['fecha_pago', 'total'])
            ->groupBy(fn($r) => Carbon::parse($r->fecha_pago)->format('Y-m'))
            ->map(fn($rows) => $rows->sum('total'));

        $meses = $ventasData = $comprasData = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha  = now()->subMonths($i);
            $key    = $fecha->format('Y-m');
            $meses[]       = ucfirst($fecha->translatedFormat('M'));
            $ventasData[]  = (float) (($ventas[$key] ?? 0) + ($logisticsIn[$key] ?? 0));
            $comprasData[] = (float) (($compras[$key] ?? 0) + ($logisticsOut[$key] ?? 0));
        }

        return [
            'labels'  => $meses,
            'ventas'  => $ventasData,
            'compras' => $comprasData,
        ];
    }
}
