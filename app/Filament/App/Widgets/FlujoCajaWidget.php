<?php

namespace App\Filament\App\Widgets;

use App\Models\CashMovement;
use App\Models\DebtPayment;
use App\Models\Sale;
use App\Models\Purchase;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Attributes\On;

class FlujoCajaWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.flujo-caja';
    
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;

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

        // Si el periodo es muy largo (año), agrupamos por mes
        if ($this->periodo === 'año') {
            return $this->getDataMensual($tenantId, $desde, $hasta);
        }

        return $this->getDataDiaria($tenantId, $desde, $hasta);
    }

    protected function getDataDiaria($tenantId, $desde, $hasta): array
    {
        $period = CarbonPeriod::create($desde, $hasta);
        $labels = [];
        $ingresosData = [];
        $egresosData = [];
        $netoData = [];

        $ingAcum = 0;
        $egrAcum = 0;

        foreach ($period as $date) {
            $labels[] = $date->format('d M');
            
            // Ingresos del día (ventas contado + movimientos caja ingreso)
            $ingDia = Sale::where('empresa_id', $tenantId)
                ->where('estado', 'confirmado')
                ->whereDate('fecha', $date)
                ->sum('total');
            
            $ingDia += CashMovement::where('empresa_id', $tenantId)
                ->where('tipo', 'ingreso')
                ->whereDate('fecha', $date)
                ->sum('monto');

            // Egresos del día (compras contado + movimientos caja egreso)
            $egrDia = Purchase::where('empresa_id', $tenantId)
                ->where('status', 'confirmado')
                ->whereDate('date', $date)
                ->sum('total');
            
            $egrDia += CashMovement::where('empresa_id', $tenantId)
                ->where('tipo', 'egreso')
                ->whereDate('fecha', $date)
                ->sum('monto');

            $egrDia += DebtPayment::where('empresa_id', $tenantId)
                ->whereDate('fecha_pago', $date)
                ->sum('total');

            $ingAcum += $ingDia;
            $egrAcum += $egrDia;

            $ingresosData[] = (float) $ingAcum;
            $egresosData[] = (float) $egrAcum;
            $netoData[] = (float) ($ingAcum - $egrAcum);
        }

        return [
            'labels' => $labels,
            'ingresos' => $ingresosData,
            'egresos' => $egresosData,
            'neto' => $netoData,
        ];
    }

    protected function getDataMensual($tenantId, $desde, $hasta): array
    {
        $labels = [];
        $ingresosData = [];
        $egresosData = [];
        $netoData = [];
        $ingAcum = 0;
        $egrAcum = 0;

        for ($i = 0; $i < 12; $i++) {
            $date = (clone $desde)->addMonths($i);
            if ($date > now()->endOfMonth()) break;

            $labels[] = ucfirst($date->translatedFormat('M'));

            $ingMes = Sale::where('empresa_id', $tenantId)
                ->where('estado', 'confirmado')
                ->whereMonth('fecha', $date->month)
                ->whereYear('fecha', $date->year)
                ->sum('total');
            
            $ingMes += CashMovement::where('empresa_id', $tenantId)
                ->where('tipo', 'ingreso')
                ->whereMonth('fecha', $date->month)
                ->whereYear('fecha', $date->year)
                ->sum('monto');

            $egrMes = Purchase::where('empresa_id', $tenantId)
                ->where('status', 'confirmado')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('total');
            
            $egrMes += CashMovement::where('empresa_id', $tenantId)
                ->where('tipo', 'egreso')
                ->whereMonth('fecha', $date->month)
                ->whereYear('fecha', $date->year)
                ->sum('monto');

            $egrMes += DebtPayment::where('empresa_id', $tenantId)
                ->whereMonth('fecha_pago', $date->month)
                ->whereYear('fecha_pago', $date->year)
                ->sum('total');

            $ingAcum += $ingMes;
            $egrAcum += $egrMes;

            $ingresosData[] = (float) $ingAcum;
            $egresosData[] = (float) $egrAcum;
            $netoData[] = (float) ($ingAcum - $egrAcum);
        }

        return [
            'labels' => $labels,
            'ingresos' => $ingresosData,
            'egresos' => $egresosData,
            'neto' => $netoData,
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
