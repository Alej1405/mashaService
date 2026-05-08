<?php

namespace App\Filament\App\Widgets;

use App\Models\CashMovement;
use App\Models\DebtPayment;
use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsShipmentBill;
use App\Models\Sale;
use App\Models\Purchase;
use App\Traits\HasDashboardPeriodo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class FlujoCajaWidget extends Widget
{
    use HasDashboardPeriodo;

    protected static string $view = 'filament.app.widgets.flujo-caja';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    protected function getViewData(): array
    {
        $tenantId = Filament::getTenant()->id;
        [$desde, $hasta] = $this->getFechas($this->periodo);

        return $this->periodo === 'año'
            ? $this->getDataMensual($tenantId, $desde, $hasta)
            : $this->getDataDiaria($tenantId, $desde, $hasta);
    }

    protected function getDataDiaria(int $tenantId, $desde, $hasta): array
    {
        $desdeStr = $desde->toDateString();
        $hastaStr = $hasta->toDateString();

        $ingVentas    = $this->fetchAndGroup(Sale::where('empresa_id', $tenantId)->where('estado', 'confirmado')->whereBetween('fecha', [$desdeStr, $hastaStr])->get(['fecha', 'total']), 'fecha', 'total');
        $ingCaja      = $this->fetchAndGroup(CashMovement::where('empresa_id', $tenantId)->where('tipo', 'ingreso')->whereBetween('fecha', [$desdeStr, $hastaStr])->get(['fecha', 'monto']), 'fecha', 'monto');
        $ingLogistica = $this->fetchAndGroup(LogisticsBillingRequest::withoutGlobalScopes()->where('empresa_id', $tenantId)->whereIn('estado', ['facturado', 'cobrado'])->whereNull('sale_id')->whereBetween('updated_at', [$desdeStr . ' 00:00:00', $hastaStr . ' 23:59:59'])->get(['updated_at', 'total']), 'updated_at', 'total');
        $egrCompras   = $this->fetchAndGroup(Purchase::where('empresa_id', $tenantId)->where('status', 'confirmado')->whereBetween('date', [$desdeStr, $hastaStr])->get(['date', 'total']), 'date', 'total');
        $egrCaja      = $this->fetchAndGroup(CashMovement::where('empresa_id', $tenantId)->where('tipo', 'egreso')->whereBetween('fecha', [$desdeStr, $hastaStr])->get(['fecha', 'monto']), 'fecha', 'monto');
        $egrDeudas    = $this->fetchAndGroup(DebtPayment::where('empresa_id', $tenantId)->whereBetween('fecha_pago', [$desdeStr, $hastaStr])->get(['fecha_pago', 'total']), 'fecha_pago', 'total');
        $egrLogistica = $this->fetchAndGroup(LogisticsShipmentBill::withoutGlobalScopes()->where('empresa_id', $tenantId)->where('estado', 'pagada')->whereBetween('fecha_pago', [$desdeStr, $hastaStr])->get(['fecha_pago', 'total']), 'fecha_pago', 'total');

        $labels = $ingresos = $egresos = $neto = [];
        $ingAcum = $egrAcum = 0;

        foreach (CarbonPeriod::create($desde, $hasta) as $date) {
            $dia      = $date->toDateString();
            $labels[] = $date->format('d M');
            $ingAcum += ($ingVentas[$dia] ?? 0) + ($ingCaja[$dia] ?? 0) + ($ingLogistica[$dia] ?? 0);
            $egrAcum += ($egrCompras[$dia] ?? 0) + ($egrCaja[$dia] ?? 0) + ($egrDeudas[$dia] ?? 0) + ($egrLogistica[$dia] ?? 0);
            $ingresos[] = (float) $ingAcum;
            $egresos[]  = (float) $egrAcum;
            $neto[]     = (float) ($ingAcum - $egrAcum);
        }

        return compact('labels', 'ingresos', 'egresos', 'neto');
    }

    protected function getDataMensual(int $tenantId, $desde, $hasta): array
    {
        $desdeStr = $desde->toDateString();
        $hastaStr = now()->endOfMonth()->toDateString();

        $ingVentas    = $this->fetchAndGroupMes(Sale::where('empresa_id', $tenantId)->where('estado', 'confirmado')->whereBetween('fecha', [$desdeStr, $hastaStr])->get(['fecha', 'total']), 'fecha', 'total');
        $ingCaja      = $this->fetchAndGroupMes(CashMovement::where('empresa_id', $tenantId)->where('tipo', 'ingreso')->whereBetween('fecha', [$desdeStr, $hastaStr])->get(['fecha', 'monto']), 'fecha', 'monto');
        $ingLogistica = $this->fetchAndGroupMes(LogisticsBillingRequest::withoutGlobalScopes()->where('empresa_id', $tenantId)->whereIn('estado', ['facturado', 'cobrado'])->whereNull('sale_id')->whereBetween('updated_at', [$desdeStr . ' 00:00:00', $hastaStr . ' 23:59:59'])->get(['updated_at', 'total']), 'updated_at', 'total');
        $egrCompras   = $this->fetchAndGroupMes(Purchase::where('empresa_id', $tenantId)->where('status', 'confirmado')->whereBetween('date', [$desdeStr, $hastaStr])->get(['date', 'total']), 'date', 'total');
        $egrCaja      = $this->fetchAndGroupMes(CashMovement::where('empresa_id', $tenantId)->where('tipo', 'egreso')->whereBetween('fecha', [$desdeStr, $hastaStr])->get(['fecha', 'monto']), 'fecha', 'monto');
        $egrDeudas    = $this->fetchAndGroupMes(DebtPayment::where('empresa_id', $tenantId)->whereBetween('fecha_pago', [$desdeStr, $hastaStr])->get(['fecha_pago', 'total']), 'fecha_pago', 'total');
        $egrLogistica = $this->fetchAndGroupMes(LogisticsShipmentBill::withoutGlobalScopes()->where('empresa_id', $tenantId)->where('estado', 'pagada')->whereBetween('fecha_pago', [$desdeStr, $hastaStr])->get(['fecha_pago', 'total']), 'fecha_pago', 'total');

        $labels = $ingresos = $egresos = $neto = [];
        $ingAcum = $egrAcum = 0;

        for ($i = 0; $i < 12; $i++) {
            $date = (clone $desde)->addMonths($i);
            if ($date > now()->endOfMonth()) break;

            $mes      = $date->format('Y-m');
            $labels[] = ucfirst($date->translatedFormat('M'));
            $ingAcum += ($ingVentas[$mes] ?? 0) + ($ingCaja[$mes] ?? 0) + ($ingLogistica[$mes] ?? 0);
            $egrAcum += ($egrCompras[$mes] ?? 0) + ($egrCaja[$mes] ?? 0) + ($egrDeudas[$mes] ?? 0) + ($egrLogistica[$mes] ?? 0);
            $ingresos[] = (float) $ingAcum;
            $egresos[]  = (float) $egrAcum;
            $neto[]     = (float) ($ingAcum - $egrAcum);
        }

        return compact('labels', 'ingresos', 'egresos', 'neto');
    }

    private function fetchAndGroup($collection, string $campoFecha, string $campoValor): \Illuminate\Support\Collection
    {
        return $collection
            ->groupBy(fn($r) => Carbon::parse($r->{$campoFecha})->toDateString())
            ->map(fn($rows) => $rows->sum($campoValor));
    }

    private function fetchAndGroupMes($collection, string $campoFecha, string $campoValor): \Illuminate\Support\Collection
    {
        return $collection
            ->groupBy(fn($r) => Carbon::parse($r->{$campoFecha})->format('Y-m'))
            ->map(fn($rows) => $rows->sum($campoValor));
    }
}
