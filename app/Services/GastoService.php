<?php

namespace App\Services;

use App\Models\ActivoFijo;
use App\Models\Depreciacion;
use App\Models\Gasto;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PorcentajeRetencion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gastos y depreciación: lo que convierte un comprobante en asiento.
 *
 * Reglas de las skills que aplica:
 *   - el IVA de la compra es crédito tributario, no gasto
 *   - la retención no es gasto: es menos caja para el proveedor
 *   - la depreciación es línea recta sobre el costo menos el valor residual
 *   - un gasto no deducible se contabiliza igual: la diferencia se concilia
 */
class GastoService
{
    public function __construct(private readonly ContabilidadService $contabilidad) {}

    /** Confirma el gasto: calcula totales, arma el asiento y la retención. */
    public function confirmar(Gasto $gasto): Gasto
    {
        return DB::transaction(function () use ($gasto) {
            $gasto->load('lineas.tipoGasto', 'supplier');

            $this->contabilidad->exigirEjercicioAbierto($gasto->empresa_id, $gasto->fecha->toDateString());

            if ($gasto->lineas->isEmpty()) {
                throw new \RuntimeException('El gasto no tiene líneas: no hay nada que contabilizar.');
            }

            $subtotal = round((float) $gasto->lineas->sum('base'), 2);
            $iva      = round((float) $gasto->lineas->sum('iva'), 2);
            $noDeducible = round((float) $gasto->lineas->where('deducible', false)->sum('base'), 2);

            $retencion = $this->retener($gasto, $subtotal, $iva);
            $retenido  = $retencion ? (float) $retencion->total_retenido : 0.0;

            $asiento = $this->asiento($gasto, $subtotal, $iva, $retenido);

            $gasto->update([
                'subtotal'         => $subtotal,
                'iva'              => $iva,
                'total'            => round($subtotal + $iva, 2),
                'retenido'         => $retenido,
                'no_deducible'     => $noDeducible,
                'journal_entry_id' => $asiento->id,
                'retencion_id'     => $retencion?->id,
                'estado'           => 'confirmado',
            ]);

            return $gasto->fresh(['lineas', 'retencion']);
        });
    }

    /** La retención que la empresa practica, si es agente de retención. */
    private function retener(Gasto $gasto, float $subtotal, float $iva): ?\App\Models\Retencion
    {
        $empresa = $gasto->empresa ?? \App\Models\Empresa::find($gasto->empresa_id);

        if (! $empresa?->agente_retencion) {
            return null;
        }

        $concepto = $gasto->lineas->pluck('tipoGasto.concepto_retencion')->filter()->first();

        if (! $concepto) {
            return null;
        }

        $calculo = $this->contabilidad->calcularRetencion(
            baseImponible: $subtotal,
            iva: $iva,
            fecha: $gasto->fecha->toDateString(),
            conceptoRenta: $concepto,
            conceptoIva: 'Servicios, comisiones y consultoría',
            empresaId: $gasto->empresa_id,
        );

        if (! $calculo['lineas']) {
            return null;
        }

        return $this->contabilidad->registrarRetencion([
            'empresa_id'  => $gasto->empresa_id,
            'supplier_id' => $gasto->supplier_id,
            'fecha'       => $gasto->fecha->toDateString(),
            'base_renta'  => $subtotal,
            'base_iva'    => $iva,
            'calculo'     => $calculo,
        ]);
    }

    /**
     * Dr gasto (por línea) · Dr IVA crédito tributario
     * Cr cuentas por pagar · Cr retenciones por pagar
     */
    private function asiento(Gasto $gasto, float $subtotal, float $iva, float $retenido): JournalEntry
    {
        $asiento = JournalEntry::create([
            'empresa_id'      => $gasto->empresa_id,
            'fecha'           => $gasto->fecha,
            'descripcion'     => 'Gasto ' . ($gasto->numero_documento ?: '') . ' · ' . ($gasto->descripcion ?: $gasto->supplier?->nombre ?? 'sin detalle'),
            'tipo'            => 'gasto',
            'origen'          => 'automatico',
            'referencia_tipo' => 'gasto',
            'referencia_id'   => $gasto->id,
            'status'          => 'confirmado',
            'total_debe'      => 0,
            'total_haber'     => 0,
            'esta_cuadrado'   => true,
            'confirmado_por'  => auth()->id(),
            'confirmado_at'   => now(),
        ]);

        $orden = 1;
        $debe = 0.0;

        foreach ($gasto->lineas as $linea) {
            $cuenta = $linea->account_plan_id ?? $linea->tipoGasto?->account_plan_id;

            if (! $cuenta) {
                throw new \RuntimeException('La línea «' . ($linea->descripcion ?: 'sin detalle') . '» no tiene cuenta contable asignada.');
            }

            JournalEntryLine::create([
                'journal_entry_id' => $asiento->id,
                'account_plan_id'  => $cuenta,
                'descripcion'      => trim(($linea->tipoGasto?->nombre ?? 'Gasto') . ' · ' . ($linea->descripcion ?? ''), ' ·')
                                        . ($linea->deducible ? '' : ' (no deducible)'),
                'debe'             => $linea->base,
                'haber'            => 0,
                'orden'            => $orden++,
            ]);
            $debe += (float) $linea->base;
        }

        if ($iva > 0 && $cuentaIva = $this->cuenta($gasto->empresa_id, '1.1.02.05')) {
            JournalEntryLine::create([
                'journal_entry_id' => $asiento->id,
                'account_plan_id'  => $cuentaIva->id,
                'descripcion'      => 'IVA crédito tributario',
                'debe'             => $iva,
                'haber'            => 0,
                'orden'            => $orden++,
            ]);
            $debe += $iva;
        }

        $porPagar = round($debe - $retenido, 2);

        if ($cuentaPago = $this->cuenta($gasto->empresa_id, '2.1.01.01') ?? $this->cuenta($gasto->empresa_id, '2.1.01')) {
            JournalEntryLine::create([
                'journal_entry_id' => $asiento->id,
                'account_plan_id'  => $cuentaPago->id,
                'descripcion'      => 'Cuentas por pagar · ' . ($gasto->supplier?->nombre ?? 'proveedor'),
                'debe'             => 0,
                'haber'            => $porPagar,
                'orden'            => $orden++,
            ]);
        }

        if ($retenido > 0 && $cuentaRet = $this->cuenta($gasto->empresa_id, '2.1.03.01') ?? $this->cuenta($gasto->empresa_id, '2.1.03')) {
            JournalEntryLine::create([
                'journal_entry_id' => $asiento->id,
                'account_plan_id'  => $cuentaRet->id,
                'descripcion'      => 'Retenciones por pagar',
                'debe'             => 0,
                'haber'            => $retenido,
                'orden'            => $orden++,
            ]);
        }

        $asiento->update(['total_debe' => $debe, 'total_haber' => $debe, 'esta_cuadrado' => true]);

        return $asiento;
    }

    private function cuenta(int $empresaId, string $codigo): ?\App\Models\AccountPlan
    {
        return \App\Models\AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('code', 'like', $codigo . '%')
            ->where('accepts_movements', true)
            ->orderBy('code')
            ->first();
    }

    /**
     * Depreciación del mes de todos los activos: una cuota por activo, un solo
     * asiento. Dr gasto depreciación · Cr depreciación acumulada.
     */
    public function depreciarMes(int $empresaId, int $anio, int $mes): array
    {
        return DB::transaction(function () use ($empresaId, $anio, $mes) {
            $this->contabilidad->exigirEjercicioAbierto($empresaId, sprintf('%04d-%02d-01', $anio, $mes));

            $activos = ActivoFijo::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)->where('activo', true)->get();

            $hechas = [];
            $total = 0.0;

            foreach ($activos as $activo) {
                if ($activo->depreciado_completo || $activo->cuota_mensual <= 0) {
                    continue;
                }

                $ya = Depreciacion::where('activo_fijo_id', $activo->id)
                    ->where('anio', $anio)->where('mes', $mes)->exists();

                if ($ya) {
                    continue;
                }

                // La última cuota ajusta para no pasarse del valor residual.
                $cuota = min($activo->cuota_mensual, $activo->valor_libros - (float) $activo->valor_residual);
                $cuota = round($cuota, 2);

                if ($cuota <= 0) {
                    continue;
                }

                Depreciacion::create([
                    'activo_fijo_id' => $activo->id,
                    'anio' => $anio, 'mes' => $mes, 'valor' => $cuota,
                ]);

                $activo->increment('depreciacion_acumulada', $cuota);
                $hechas[] = ['activo' => $activo->nombre, 'valor' => $cuota];
                $total += $cuota;
            }

            return ['activos' => count($hechas), 'total' => round($total, 2), 'detalle' => $hechas];
        });
    }
}
