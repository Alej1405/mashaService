<?php

namespace App\Filament\App\Widgets;

use App\Models\AccountPlan;
use App\Models\JournalEntryLine;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Livewire\Attributes\On;

class ResumenFinancieroWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.resumen-financiero';
    
    protected int | string | array $columnSpan = [
        'default' => 1,
        'sm'      => 2,
        'md'      => 2,
        'lg'      => 2,
        'xl'      => 2,
    ];

    protected static ?int $sort = 2;

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
        $tenant = Filament::getTenant();
        [$desde, $hasta] = $this->getFechas($this->periodo);
        
        // Datos Período Actual
        $actual = $this->getMetricas($tenant->id, $desde, $hasta);
        
        // Datos Período Anterior
        $diff = $desde->diffInDays($hasta) + 1;
        $desdePasado = (clone $desde)->subDays($diff);
        $hastaPasado = (clone $hasta)->subDays($diff);
        $pasado = $this->getMetricas($tenant->id, $desdePasado, $hastaPasado);

        $varIngresos = $pasado['ingresos'] > 0 
            ? (($actual['ingresos'] - $pasado['ingresos']) / $pasado['ingresos']) * 100 
            : 0;

        $margenBruto = $actual['ingresos'] > 0 ? ($actual['utilBruta'] / $actual['ingresos']) * 100 : 0;
        $margenNeto = $actual['ingresos'] > 0 ? ($actual['utilNeta'] / $actual['ingresos']) * 100 : 0;
        $eficiencia = $actual['ingresos'] > 0 ? ($actual['gastosOp'] / $actual['ingresos']) * 100 : 0;

        return [
            'actual' => $actual,
            'varIngresos' => $varIngresos,
            'semáforos' => [
                'bruto' => [
                    'label' => 'Margen Bruto',
                    'valor' => $margenBruto,
                    'color' => $margenBruto > 30 ? 'success' : ($margenBruto > 15 ? 'warning' : 'danger'),
                    'msg' => $margenBruto > 30 ? 'Estructura sana' : 'Revisar costos'
                ],
                'eficiencia' => [
                    'label' => 'Eficiencia',
                    'valor' => $eficiencia,
                    'color' => $eficiencia < 20 ? 'success' : ($eficiencia < 35 ? 'warning' : 'danger'),
                    'msg' => $eficiencia < 20 ? 'Gasto controlado' : 'Gasto elevado'
                ],
                'rentabilidad' => [
                    'label' => 'Rentabilidad',
                    'valor' => $margenNeto,
                    'color' => $margenNeto > 10 ? 'success' : ($margenNeto > 5 ? 'warning' : 'danger'),
                    'msg' => $margenNeto > 10 ? 'Alta rentabilidad' : 'Bajo margen'
                ],
            ]
        ];
    }

    protected function getMetricas($empresaId, $desde, $hasta): array
    {
        $ingresos = $this->sum($empresaId, ['4.1', '4.2', '4.3'], 'ingreso', 'haber', $desde, $hasta);
        $costos = $this->sum($empresaId, ['5'], 'costo', 'debe', $desde, $hasta);
        $gastosOp = $this->sum($empresaId, ['6.1', '6.2'], 'gasto', 'debe', $desde, $hasta);
        $gastosNoOp = $this->sum($empresaId, ['6.3', '6.4'], 'gasto', 'debe', $desde, $hasta);

        $utilBruta = $ingresos - $costos;
        $utilAntes = $utilBruta - $gastosOp - $gastosNoOp;
        $part = $utilAntes > 0 ? $utilAntes * 0.15 : 0;
        $ir = ($utilAntes - $part) > 0 ? ($utilAntes - $part) * 0.25 : 0;
        $impuestos = $part + $ir;
        $utilNeta = $utilAntes - $impuestos;

        return [
            'ingresos' => $ingresos,
            'costosGastos' => $costos + $gastosOp + $gastosNoOp,
            'gastosOp' => $gastosOp,
            'utilBruta' => $utilBruta,
            'utilNeta' => $utilNeta,
            'impuestos' => $impuestos,
            'utilAntes' => $utilAntes
        ];
    }

    protected function sum($empresaId, $codes, $type, $field, $desde, $hasta): float
    {
        return (float) AccountPlan::where('empresa_id', $empresaId)
            ->where('type', $type)
            ->where(function($q) use ($codes) {
                foreach($codes as $c) $q->orWhere('code', 'like', $c . '%');
            })
            ->where('accepts_movements', true)
            ->get()
            ->sum(function($cuenta) use ($empresaId, $desde, $hasta, $field) {
                return JournalEntryLine::where('account_plan_id', $cuenta->id)
                    ->whereHas('journalEntry', fn($q) => $q
                        ->where('empresa_id', $empresaId)
                        ->where('status', 'confirmado')
                        ->where('esta_cuadrado', true)
                        ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                    )->sum($field);
            });
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
