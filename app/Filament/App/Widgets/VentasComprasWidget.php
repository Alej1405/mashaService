<?php

namespace App\Filament\App\Widgets;

use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsShipmentBill;
use App\Models\Sale;
use App\Models\Purchase;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class VentasComprasWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.ventas-compras';
    
    protected int | string | array $columnSpan = [
        'default' => 1,
        'sm'      => 2,
        'md'      => 1,
        'lg'      => 1,
        'xl'      => 1,
    ];

    protected static ?int $sort = 3;

    protected function getViewData(): array
    {
        $tenantId = Filament::getTenant()->id;
        $meses = [];
        $ventasData = [];
        $comprasData = [];

        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $meses[] = ucfirst($fecha->translatedFormat('M'));
            
            // Ventas: productos confirmados + servicios logísticos facturados/cobrados sin venta aún
            $ventas = (float) Sale::where('empresa_id', $tenantId)
                ->where('estado', 'confirmado')
                ->whereMonth('fecha', $fecha->month)
                ->whereYear('fecha', $fecha->year)
                ->sum('total');

            $ventas += (float) LogisticsBillingRequest::withoutGlobalScopes()
                ->where('empresa_id', $tenantId)
                ->whereIn('estado', ['facturado', 'cobrado'])
                ->whereNull('sale_id')
                ->whereMonth('updated_at', $fecha->month)
                ->whereYear('updated_at', $fecha->year)
                ->sum('total');

            $ventasData[] = $ventas;

            // Compras: productos + facturas proveedor logística pagadas
            $compras = (float) Purchase::where('empresa_id', $tenantId)
                ->where('status', 'confirmado')
                ->whereMonth('date', $fecha->month)
                ->whereYear('date', $fecha->year)
                ->sum('total');

            $compras += (float) LogisticsShipmentBill::withoutGlobalScopes()
                ->where('empresa_id', $tenantId)
                ->where('estado', 'pagada')
                ->whereMonth('fecha_pago', $fecha->month)
                ->whereYear('fecha_pago', $fecha->year)
                ->sum('total');

            $comprasData[] = $compras;
        }

        return [
            'labels' => $meses,
            'ventas' => $ventasData,
            'compras' => $comprasData,
        ];
    }
}
