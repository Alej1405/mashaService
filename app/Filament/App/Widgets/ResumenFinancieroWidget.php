<?php

namespace App\Filament\App\Widgets;

use App\Traits\HasDashboardPeriodo;
use App\Traits\SumarizaCuentasContables;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class ResumenFinancieroWidget extends Widget
{
    use HasDashboardPeriodo, SumarizaCuentasContables;

    protected static string $view = 'filament.app.widgets.resumen-financiero';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'sm'      => 2,
        'md'      => 2,
        'lg'      => 2,
        'xl'      => 2,
    ];

    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();
        [$desde, $hasta] = $this->getFechas($this->periodo);

        $actual = $this->getMetricas($tenant->id, $desde, $hasta);

        $diff        = $desde->diffInDays($hasta) + 1;
        $desdePasado = (clone $desde)->subDays($diff);
        $hastaPasado = (clone $hasta)->subDays($diff);
        $pasado      = $this->getMetricas($tenant->id, $desdePasado, $hastaPasado);

        $varIngresos = $pasado['ingresos'] > 0
            ? (($actual['ingresos'] - $pasado['ingresos']) / $pasado['ingresos']) * 100
            : 0;

        $margenBruto = $actual['ingresos'] > 0 ? ($actual['utilBruta'] / $actual['ingresos']) * 100 : 0;
        $margenNeto  = $actual['ingresos'] > 0 ? ($actual['utilNeta'] / $actual['ingresos']) * 100 : 0;
        $eficiencia  = $actual['ingresos'] > 0 ? ($actual['gastosOp'] / $actual['ingresos']) * 100 : 0;

        return [
            'actual'      => $actual,
            'varIngresos' => $varIngresos,
            'semáforos'   => [
                'bruto'        => ['label' => 'Margen Bruto',   'valor' => $margenBruto, 'color' => $margenBruto > 30 ? 'success' : ($margenBruto > 15 ? 'warning' : 'danger'), 'msg' => $margenBruto > 30 ? 'Estructura sana' : 'Revisar costos'],
                'eficiencia'   => ['label' => 'Eficiencia',     'valor' => $eficiencia,  'color' => $eficiencia < 20  ? 'success' : ($eficiencia < 35  ? 'warning' : 'danger'), 'msg' => $eficiencia < 20  ? 'Gasto controlado' : 'Gasto elevado'],
                'rentabilidad' => ['label' => 'Rentabilidad',   'valor' => $margenNeto,  'color' => $margenNeto > 10  ? 'success' : ($margenNeto > 5   ? 'warning' : 'danger'), 'msg' => $margenNeto > 10  ? 'Alta rentabilidad' : 'Bajo margen'],
            ],
        ];
    }

    protected function getMetricas(int $empresaId, $desde, $hasta): array
    {
        $ingresos   = $this->sumCuentas($empresaId, ['4.1', '4.2', '4.3'], 'ingreso', 'haber', $desde, $hasta);
        $costos     = $this->sumCuentas($empresaId, ['5'], 'costo', 'debe', $desde, $hasta);
        $gastosOp   = $this->sumCuentas($empresaId, ['6.1', '6.2'], 'gasto', 'debe', $desde, $hasta);
        $gastosNoOp = $this->sumCuentas($empresaId, ['6.3', '6.4'], 'gasto', 'debe', $desde, $hasta);

        $utilBruta  = $ingresos - $costos;
        $utilAntes  = $utilBruta - $gastosOp - $gastosNoOp;
        $part       = $utilAntes > 0 ? $utilAntes * 0.15 : 0;
        $ir         = ($utilAntes - $part) > 0 ? ($utilAntes - $part) * 0.25 : 0;
        $impuestos  = $part + $ir;

        return [
            'ingresos'     => $ingresos,
            'costosGastos' => $costos + $gastosOp + $gastosNoOp,
            'gastosOp'     => $gastosOp,
            'utilBruta'    => $utilBruta,
            'utilNeta'     => $utilAntes - $impuestos,
            'impuestos'    => $impuestos,
            'utilAntes'    => $utilAntes,
        ];
    }
}
