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
     *
     * Método: interés y seguro calculados con días exactos entre fechas,
     * base 360 (práctica estándar bancos Ecuador: saldo × tasa_anual/360 × días).
     */
    public function generarTablaAmortizacion(Debt $debt): array
    {
        $n = $debt->numero_cuotas;
        if (!$n || $n <= 0) {
            return [];
        }

        $capital     = (float) $debt->monto_original;
        $seguroAnual = (float) $debt->seguro_desgravamen_anual; // % anual nominal

        // tasa_interes siempre es TNA (Tasa Nominal Anual) en %
        $tasaAnual = (float) $debt->tasa_interes;

        // Tasas diarias base 360 (método bancos Ecuador)
        $tasaDiaria   = $tasaAnual / 100 / 360;
        $seguroDiario = $seguroAnual / 100 / 360;

        // Tasa mensual combinada para calcular cuota fija PMT
        $tasaMensualCombinada = ($tasaAnual + $seguroAnual) / 100 / 12;

        $tabla         = [];
        $saldo         = $capital;
        $fechaAnterior = Carbon::parse($debt->fecha_inicio);

        if ($debt->sistema_amortizacion === 'frances') {
            // Si la deuda fue registrada con cuota conocida, usarla directamente.
            // Si no, se calcula con la fórmula PMT estándar.
            $cuotaFija = ($debt->cuota_mensual && (float) $debt->cuota_mensual > 0)
                ? (float) $debt->cuota_mensual
                : $this->calcularCuotaMensual($capital, $tasaMensualCombinada, $n);

            for ($i = 1; $i <= $n; $i++) {
                $fechaActual = $fechaAnterior->copy()->addMonth();
                $dias        = (int) $fechaAnterior->diffInDays($fechaActual);

                $interes           = round($saldo * $tasaDiaria * $dias, 2);
                $seguroDesgravamen = round($saldo * $seguroDiario * $dias, 2);

                // Última cuota: salda el remanente exacto
                $capitalPagado = ($i === $n)
                    ? round($saldo, 2)
                    : round($cuotaFija - $interes - $seguroDesgravamen, 2);

                $saldoFinal = max(0, round($saldo - $capitalPagado, 2));
                $totalCuota = round($capitalPagado + $interes + $seguroDesgravamen, 2);

                $tabla[] = [
                    'numero_cuota'       => $i,
                    'fecha_vencimiento'  => $fechaActual->toDateString(),
                    'saldo_inicial'      => round($saldo, 2),
                    'monto_interes'      => $interes,
                    'seguro_desgravamen' => $seguroDesgravamen,
                    'monto_capital'      => $capitalPagado,
                    'total_cuota'        => $totalCuota,
                    'saldo_final'        => $saldoFinal,
                    'estado'             => 'pendiente',
                ];

                $saldo         = $saldoFinal;
                $fechaAnterior = $fechaActual;
            }
        } elseif ($debt->sistema_amortizacion === 'aleman') {
            // Capital fijo = P/n; interés y seguro sobre saldo decreciente por días exactos
            $capitalFijo = round($capital / $n, 2);

            for ($i = 1; $i <= $n; $i++) {
                $fechaActual = $fechaAnterior->copy()->addMonth();
                $dias        = (int) $fechaAnterior->diffInDays($fechaActual);

                $interes           = round($saldo * $tasaDiaria * $dias, 2);
                $seguroDesgravamen = round($saldo * $seguroDiario * $dias, 2);
                $capitalPagado     = ($i === $n) ? round($saldo, 2) : $capitalFijo;
                $saldoFinal        = max(0, round($saldo - $capitalPagado, 2));

                $tabla[] = [
                    'numero_cuota'       => $i,
                    'fecha_vencimiento'  => $fechaActual->toDateString(),
                    'saldo_inicial'      => round($saldo, 2),
                    'monto_interes'      => $interes,
                    'seguro_desgravamen' => $seguroDesgravamen,
                    'monto_capital'      => $capitalPagado,
                    'total_cuota'        => round($capitalPagado + $interes + $seguroDesgravamen, 2),
                    'saldo_final'        => $saldoFinal,
                    'estado'             => 'pendiente',
                ];

                $saldo         = $saldoFinal;
                $fechaAnterior = $fechaActual;
            }
        } elseif ($debt->sistema_amortizacion === 'americano') {
            // Solo intereses cada período; capital completo en la última cuota
            for ($i = 1; $i <= $n; $i++) {
                $fechaActual = $fechaAnterior->copy()->addMonth();
                $dias        = (int) $fechaAnterior->diffInDays($fechaActual);

                $interes           = round($saldo * $tasaDiaria * $dias, 2);
                $seguroDesgravamen = round($saldo * $seguroDiario * $dias, 2);
                $capitalPagado     = ($i === $n) ? round($saldo, 2) : 0.00;
                $saldoFinal        = max(0, round($saldo - $capitalPagado, 2));

                $tabla[] = [
                    'numero_cuota'       => $i,
                    'fecha_vencimiento'  => $fechaActual->toDateString(),
                    'saldo_inicial'      => round($saldo, 2),
                    'monto_interes'      => $interes,
                    'seguro_desgravamen' => $seguroDesgravamen,
                    'monto_capital'      => $capitalPagado,
                    'total_cuota'        => round($capitalPagado + $interes + $seguroDesgravamen, 2),
                    'saldo_final'        => $saldoFinal,
                    'estado'             => 'pendiente',
                ];

                $saldo         = $saldoFinal;
                $fechaAnterior = $fechaActual;
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
