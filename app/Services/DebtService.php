<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\DebtAmortizationLine;
use Carbon\Carbon;

class DebtService
{
    /**
     * Calcula la cuota mensual fija (sistema francés).
     */
    public function calcularCuotaMensual(float $capital, float $tasaMensual, int $numCuotas): float
    {
        if ($tasaMensual == 0 || $numCuotas == 0) {
            return $numCuotas > 0 ? $capital / $numCuotas : 0;
        }

        return $capital * $tasaMensual * pow(1 + $tasaMensual, $numCuotas)
            / (pow(1 + $tasaMensual, $numCuotas) - 1);
    }

    /**
     * Genera la tabla de amortización completa para una deuda.
     */
    public function generarTablaAmortizacion(Debt $debt): array
    {
        $n = $debt->numero_cuotas;
        if (!$n || $n <= 0) {
            return [];
        }

        $capital = (float) $debt->monto_original;
        $tasa    = (float) $debt->tasa_interes / 100;

        // Convertir tasa a mensual
        $tasaMensual = $debt->frecuencia_tasa === 'anual' ? $tasa / 12 : $tasa;

        $tabla  = [];
        $saldo  = $capital;
        $fecha  = Carbon::parse($debt->fecha_inicio);

        if ($debt->tipo_tasa === 'compuesto') {
            // Sistema Francés: cuota fija, interés sobre saldo decreciente
            $cuotaFija = $this->calcularCuotaMensual($capital, $tasaMensual, $n);

            for ($i = 1; $i <= $n; $i++) {
                $fecha->addMonth();
                $interes       = round($saldo * $tasaMensual, 2);
                $capitalPagado = round($cuotaFija - $interes, 2);

                // Última cuota: ajustar saldo exacto para evitar decimales
                if ($i === $n) {
                    $capitalPagado = $saldo;
                }

                $saldoFinal = max(0, round($saldo - $capitalPagado, 2));
                $totalCuota = round($capitalPagado + $interes, 2);

                $tabla[] = [
                    'numero_cuota'    => $i,
                    'fecha_vencimiento' => $fecha->copy()->toDateString(),
                    'saldo_inicial'   => round($saldo, 2),
                    'monto_interes'   => $interes,
                    'monto_capital'   => $capitalPagado,
                    'total_cuota'     => $totalCuota,
                    'saldo_final'     => $saldoFinal,
                    'estado'          => 'pendiente',
                ];

                $saldo = $saldoFinal;
            }
        } else {
            // Interés simple: interés calculado sobre el capital original
            $plazoAnios     = ($debt->plazo_meses ?? ($n)) / 12;
            $totalInteres   = $capital * $tasa * $plazoAnios;
            $interesPorCuota = round($totalInteres / $n, 2);
            $capitalPorCuota = round($capital / $n, 2);

            for ($i = 1; $i <= $n; $i++) {
                $fecha->addMonth();

                // Última cuota ajusta residuo de redondeo
                if ($i === $n) {
                    $capitalPorCuota = round($saldo, 2);
                }

                $saldoFinal = max(0, round($saldo - $capitalPorCuota, 2));

                $tabla[] = [
                    'numero_cuota'    => $i,
                    'fecha_vencimiento' => $fecha->copy()->toDateString(),
                    'saldo_inicial'   => round($saldo, 2),
                    'monto_interes'   => $interesPorCuota,
                    'monto_capital'   => $capitalPorCuota,
                    'total_cuota'     => round($capitalPorCuota + $interesPorCuota, 2),
                    'saldo_final'     => $saldoFinal,
                    'estado'          => 'pendiente',
                ];

                $saldo = $saldoFinal;
            }
        }

        return $tabla;
    }

    /**
     * Genera y persiste las líneas de amortización para una deuda.
     */
    public function generarLineasAmortizacion(Debt $debt): void
    {
        $debt->amortizationLines()->delete();

        $tabla = $this->generarTablaAmortizacion($debt);

        foreach ($tabla as $row) {
            $debt->amortizationLines()->create($row);
        }
    }

    /**
     * Recalcula saldo pendiente y actualiza el estado de la deuda.
     */
    public function actualizarSaldoYEstado(Debt $debt): void
    {
        $debt->refresh();

        $totalCapitalPagado = $debt->payments()->sum('monto_capital');
        $totalPagado        = $debt->payments()->sum('total');
        $saldoPendiente     = max(0, round($debt->monto_original - $totalCapitalPagado, 2));

        $estado = $debt->estado;

        if ($saldoPendiente <= 0) {
            $estado = 'pagada';
        } elseif ($totalCapitalPagado > 0) {
            $estado = 'parcial';
        } elseif ($debt->fecha_vencimiento < now()->toDateString()) {
            $estado = 'vencida';
        }

        $debt->updateQuietly([
            'saldo_pendiente' => $saldoPendiente,
            'total_pagado'    => $totalPagado,
            'estado'          => $estado,
        ]);

        // Marcar cuotas vencidas no pagadas
        $debt->amortizationLines()
            ->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->update(['estado' => 'vencida']);
    }
}
