<?php

namespace App\Filament\App\Widgets;

use App\Models\AccountPlan;
use App\Models\JournalEntryLine;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class EstadoResultadosWidget extends Widget
{
    protected static ?int $sort = 99;

    protected static string $view = 'filament.app.widgets.estado-resultados-widget';
    
    protected int|string|array $columnSpan = 'full';
    
    public string $periodo = 'mes';

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();
        [$fechaDesde, $fechaHasta] = $this->getRangoFechas($this->periodo);
        
        // Datos actuales
        $dataActual = $this->obtenerMetricas($tenant->id, $fechaDesde, $fechaHasta);
        
        // Datos mes anterior (para variación de ingresos)
        $inicioPasado = (clone $fechaDesde)->subMonth();
        $finPasado = (clone $fechaHasta)->subMonth();
        $dataPasada = $this->obtenerMetricas($tenant->id, $inicioPasado, $finPasado);
        
        $variacionIngresos = $dataPasada['ingresos'] > 0 
            ? (($dataActual['ingresos'] - $dataPasada['ingresos']) / $dataPasada['ingresos']) * 100 
            : 0;

        // Indicadores de Salud
        $margenBruto = $dataActual['ingresos'] > 0 ? ($dataActual['utilidadBruta'] / $dataActual['ingresos']) * 100 : 0;
        $margenNeto = $dataActual['ingresos'] > 0 ? ($dataActual['utilidadNeta'] / $dataActual['ingresos']) * 100 : 0;
        $cargaFiscal = $dataActual['utilidadAntesImp'] > 0 ? ($dataActual['impuestos'] / $dataActual['utilidadAntesImp']) * 100 : 0;
        $eficienciaOp = $dataActual['ingresos'] > 0 ? ($dataActual['gastosOp'] / $dataActual['ingresos']) * 100 : 0;

        return [
            'periodoActual' => $this->periodo,
            'fechaDesde' => $fechaDesde->format('d/m/Y'),
            'fechaHasta' => $fechaHasta->format('d/m/Y'),
            'metrics' => $dataActual,
            'variacionIngresos' => $variacionIngresos,
            'salud' => [
                'margenBruto' => [
                    'valor' => $margenBruto,
                    'color' => $margenBruto > 30 ? 'success' : ($margenBruto > 15 ? 'warning' : 'danger')
                ],
                'margenNeto' => [
                    'valor' => $margenNeto,
                    'color' => $margenNeto > 10 ? 'success' : ($margenNeto > 5 ? 'warning' : 'danger')
                ],
                'cargaFiscal' => [
                    'valor' => $cargaFiscal,
                    'color' => $cargaFiscal < 35 ? 'success' : ($cargaFiscal < 40 ? 'warning' : 'danger')
                ],
                'eficienciaOp' => [
                    'valor' => $eficienciaOp,
                    'color' => $eficienciaOp < 20 ? 'success' : ($eficienciaOp < 35 ? 'warning' : 'danger')
                ],
            ],
            'interpretacion' => $this->generarInterpretacion($dataActual, $margenNeto),
        ];
    }

    protected function obtenerMetricas($empresaId, $desde, $hasta): array
    {
        $ingresosOrd = $this->sumCuentas($empresaId, ['4.1'], 'ingreso', 'haber', $desde, $hasta);
        $otrosIngresos = $this->sumCuentas($empresaId, ['4.2', '4.3'], 'ingreso', 'haber', $desde, $hasta);
        $costos = $this->sumCuentas($empresaId, ['5'], 'costo', 'debe', $desde, $hasta);
        $gastosOp = $this->sumCuentas($empresaId, ['6.1', '6.2'], 'gasto', 'debe', $desde, $hasta);
        $gastosNoOp = $this->sumCuentas($empresaId, ['6.3', '6.4'], 'gasto', 'debe', $desde, $hasta);

        $totIng = $ingresosOrd + $otrosIngresos;
        $utilBruta = $totIng - $costos;
        $utilOp = $utilBruta - $gastosOp;
        $utilAntesImp = $utilOp - $gastosNoOp;
        
        $part = $utilAntesImp > 0 ? $utilAntesImp * 0.15 : 0;
        $ir = ($utilAntesImp - $part) > 0 ? ($utilAntesImp - $part) * 0.25 : 0;
        $impuestos = $part + $ir;
        
        $utilNeta = $utilAntesImp - $impuestos;

        return [
            'ingresos' => $totIng,
            'costos' => $costos,
            'utilidadBruta' => $utilBruta,
            'gastosOp' => $gastosOp,
            'utilidadAntesImp' => $utilAntesImp,
            'impuestos' => $impuestos,
            'part' => $part,
            'ir' => $ir,
            'utilidadNeta' => $utilNeta,
            'gastosNoOp' => $gastosNoOp,
        ];
    }

    protected function sumCuentas($empresaId, $codes, $type, $field, $desde, $hasta): float
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

    public function getRangoFechas(): array
    {
        return match($this->periodo) {
            'mes'       => [now()->startOfMonth(), now()->endOfMonth()],
            'trimestre' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'año'       => [now()->startOfYear(), now()->endOfYear()],
            default     => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    protected function generarInterpretacion($data, $margenNeto): array
    {
        $msgs = [];
        
        if ($margenNeto > 10) {
            $msgs[] = "✅ La empresa es rentable. Por cada $100 vendidos genera $" . number_format($margenNeto, 2) . " de ganancia neta.";
        } elseif ($margenNeto > 0) {
            $msgs[] = "⚠️ La empresa opera con margen ajustado. Considere revisar costos y gastos.";
        } else {
            $msgs[] = "🔴 La empresa opera a pérdida este período. Ingresos insuficientes para cubrir costos.";
        }

        if ($data['costos'] > $data['ingresos'] * 0.7 && $data['ingresos'] > 0) {
            $msgs[] = "⚠️ El costo de ventas representa más del 70% de los ingresos. Revisar precios o proveedores.";
        }

        if ($data['gastosOp'] > $data['ingresos'] * 0.3 && $data['ingresos'] > 0) {
            $msgs[] = "⚠️ Los gastos operacionales son elevados respecto a los ingresos.";
        }

        if ($data['utilidadNeta'] > 0) {
            $msgs[] = "💰 Obligaciones fiscales estimadas: 15% trabajadores: $" . number_format($data['part'], 2) . " | 25% IR: $" . number_format($data['ir'], 2) . " | Total: $" . number_format($data['impuestos'], 2);
        }

        return $msgs;
    }

    public function setPeriodo($p)
    {
        $this->periodo = $p;
    }
}
