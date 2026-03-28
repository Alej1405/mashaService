<?php

namespace App\Http\Controllers;

use App\Models\CostoFijo;
use App\Models\Debt;
use App\Models\DebtAmortizationLine;
use App\Models\Empresa;
use App\Models\ProductDesign;
use App\Models\ProductSimulation;
use Illuminate\Http\Request;

class ProductDesignPrintController extends Controller
{
    public function equilibrio(Request $request, string $empresaSlug, int $designId)
    {
        $empresa = Empresa::where('slug', $empresaSlug)->firstOrFail();
        $design  = ProductDesign::withoutGlobalScopes()->findOrFail($designId);

        // Verificar acceso
        abort_unless(auth()->user()->empresa_id === $empresa->id || auth()->user()->hasRole('super_admin'), 403);
        abort_unless($design->empresa_id === $empresa->id, 403);

        // Cargar simulación
        $simulationId = $request->query('simulation');
        $simulation   = $simulationId
            ? ProductSimulation::where('empresa_id', $empresa->id)->find($simulationId)
            : ProductSimulation::where('empresa_id', $empresa->id)
                ->where('product_design_id', $design->id)
                ->latest()
                ->first();

        abort_if(!$simulation, 404, 'No hay simulación guardada para este diseño. Guarda una simulación desde la pestaña Simulación y Análisis.');

        // ── Datos base ──────────────────────────────────────────────────────────
        $cantidad    = (float) $simulation->cantidad;
        $pvpSinIva   = (float) $simulation->pvp_sin_iva;
        $costoTotal  = (float) $simulation->costo_total;
        $capacidad   = (float) ($design->capacidad_instalada_mensual ?? 0);
        $fracMes     = $capacidad > 0 ? $cantidad / $capacidad : 0;

        // Si la simulación no tiene PVP guardado, recalcular desde el margen
        if ($pvpSinIva <= 0 && $cantidad > 0 && $costoTotal > 0) {
            $costoUnitTmp = $costoTotal / $cantidad;
            $margenPct    = (float) ($simulation->margen_porcentaje ?? 0);
            if ($margenPct <= 0) {
                // Buscar margen_objetivo de la presentación en el diseño
                $presNombre   = $simulation->presentation_nombre;
                $presentation = $design->presentations()->where('nombre', $presNombre)->first();
                $margenPct    = (float) ($presentation->margen_objetivo ?? 30);
                if ($margenPct <= 0) $margenPct = 30;
            }
            $div       = 1 - ($margenPct / 100);
            $pvpSinIva = $div > 0 ? round($costoUnitTmp / $div, 2) : 0;
        }
        $pvpConIva = round($pvpSinIva * 1.15, 2);

        // ── Costos fijos actuales ─────────────────────────────────────────────
        $costosFijos        = CostoFijo::where('empresa_id', $empresa->id)->where('activo', true)->get();
        $totalFijosMensual  = $costosFijos->sum(fn ($c) => $c->monto_mensual);
        $totalFijosProrr    = $totalFijosMensual * $fracMes;

        // ── Estimación costo variable (extrayendo fijos del costo total) ──────
        $costoVariable  = max($costoTotal - $totalFijosProrr, 0);
        $costoVarUnit   = $cantidad > 0 ? $costoVariable / $cantidad : 0;
        $costoUnitario  = $cantidad > 0 ? $costoTotal / $cantidad : 0;

        // ── Contribución y PE operativo (solo costos fijos) ───────────────────
        $contribucionUnit = $pvpSinIva - $costoVarUnit;
        $peUnidades       = $contribucionUnit > 0 ? (int) ceil($totalFijosMensual / $contribucionUnit) : null;
        $peMonetario      = $peUnidades !== null ? $peUnidades * $pvpSinIva : null;
        $coberturaOp      = ($peUnidades !== null && $peUnidades > 0)
            ? min(round($cantidad / $peUnidades * 100, 1), 999)
            : null;

        // ── Servicio de deuda del mes ─────────────────────────────────────────
        $servicioDeudasMes = (float) DebtAmortizationLine::whereHas(
            'debt', fn ($q) => $q->where('empresa_id', $empresa->id)
        )
        ->whereMonth('fecha_vencimiento', now()->month)
        ->whereYear('fecha_vencimiento', now()->year)
        ->where('estado', '!=', 'pagada')
        ->sum('total_cuota');

        // Detalle de cuotas del mes
        $cuotasMes = DebtAmortizationLine::whereHas(
            'debt', fn ($q) => $q->where('empresa_id', $empresa->id)
        )
        ->whereMonth('fecha_vencimiento', now()->month)
        ->whereYear('fecha_vencimiento', now()->year)
        ->where('estado', '!=', 'pagada')
        ->with('debt')
        ->orderBy('fecha_vencimiento')
        ->get();

        // ── PE total (fijos + deudas) ─────────────────────────────────────────
        $totalCompromisos   = $totalFijosMensual + $servicioDeudasMes;
        $peTotalUnidades    = ($contribucionUnit > 0 && $totalCompromisos > 0)
            ? (int) ceil($totalCompromisos / $contribucionUnit)
            : null;
        $peTotalMonetario   = $peTotalUnidades !== null ? $peTotalUnidades * $pvpSinIva : null;
        $contribucionTotal  = $contribucionUnit * $cantidad;
        $coberturaTotal     = ($peTotalUnidades !== null && $peTotalUnidades > 0)
            ? min(round($cantidad / $peTotalUnidades * 100, 1), 999)
            : null;
        $pctAporte          = $totalCompromisos > 0
            ? min(round($contribucionTotal / $totalCompromisos * 100, 1), 999)
            : 0;

        // Distribuidor
        $pvpDist         = (float) ($design->precio_distribuidor ?? 0);
        $contribDistUnit = $pvpDist > 0 ? $pvpDist - $costoVarUnit : null;
        $peDist          = ($contribDistUnit !== null && $contribDistUnit > 0)
            ? (int) ceil($totalFijosMensual / $contribDistUnit)
            : null;

        // ── Otras simulaciones del mismo diseño ───────────────────────────────
        $otrasSimulaciones = ProductSimulation::where('empresa_id', $empresa->id)
            ->where('product_design_id', $design->id)
            ->latest()
            ->get();

        $download = $request->boolean('download');
        $isPdf    = $download;

        $data = compact(
            'empresa', 'design', 'simulation', 'otrasSimulaciones', 'isPdf',
            'cantidad', 'pvpSinIva', 'pvpConIva', 'costoTotal', 'costoUnitario',
            'costoVariable', 'costoVarUnit', 'capacidad', 'fracMes',
            'costosFijos', 'totalFijosMensual', 'totalFijosProrr',
            'contribucionUnit', 'contribucionTotal',
            'peUnidades', 'peMonetario', 'coberturaOp',
            'servicioDeudasMes', 'cuotasMes',
            'totalCompromisos', 'peTotalUnidades', 'peTotalMonetario',
            'coberturaTotal', 'pctAporte',
            'pvpDist', 'contribDistUnit', 'peDist'
        );

        if ($download) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('product-design.equilibrio-report', $data)
                ->setPaper('a4', 'portrait');
            $filename = 'PE-' . str($design->nombre)->slug() . '-' . now()->format('Ymd') . '.pdf';
            return $pdf->download($filename);
        }

        return view('product-design.equilibrio-report', $data);
    }
}
