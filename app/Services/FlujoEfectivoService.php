<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Estado de flujo de efectivo con el formato de la Superintendencia (líneas 95 a 98).
 *
 * No sale de saldos sino de los movimientos de caja y bancos: cada asiento que toca
 * el efectivo se clasifica por su contrapartida (método directo). La conciliación
 * (96 a 9820) parte del resultado del ejercicio y tiene que llegar al mismo flujo de
 * operación; si no llega, la diferencia queda en 9711 y se ve.
 */
class FlujoEfectivoService
{
    /** Asientos que no mueven efectivo ni son de la operación: no entran a los cambios en activos y pasivos. */
    private const SIN_EFECTIVO = ['aporte_capital', 'saldo_cuadre', 'depreciacion'];

    /** @return array<string, float> código del catálogo => valor */
    public function calcular(int $empresaId, int $anio): array
    {
        $lineas = DB::table('journal_entry_lines as l')
            ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('account_plans as a', 'a.id', '=', 'l.account_plan_id')
            ->where('j.empresa_id', $empresaId)->where('j.status', 'confirmado')
            ->whereYear('j.fecha', $anio)
            ->get(['j.id', 'j.referencia_tipo', 'j.referencia_id', 'a.code', DB::raw('l.debe - l.haber as v')])
            ->groupBy('id');

        // Compras que después pasaron a cuenta por cobrar de la socia: su pago es un anticipo a un tercero.
        $aLaSocia = $lineas->filter(fn ($l) => $l->first()->referencia_tipo === 'purchase'
                && ! $l->contains(fn ($x) => $this->esEfectivo($x->code))
                && $l->contains(fn ($x) => str_starts_with($x->code, '1.1.02.04') && $x->v > 0))
            ->map(fn ($l) => $l->first()->referencia_id)->values()->all();

        $f = array_fill_keys(['95010101', '95010105', '95010201', '950209', '950208', '950215', '950216',
                              '950301', '950304', '950305'], 0.0);
        $cambios = array_fill_keys(['9801', '9804', '9805', '9806', '9807'], 0.0);
        $depreciacion = 0.0;
        $resultado = 0.0;

        foreach ($lineas as $asiento) {
            $cab = $asiento->first();
            $efectivo = round($asiento->filter(fn ($x) => $this->esEfectivo($x->code))->sum('v'), 2);
            $contra = $asiento->reject(fn ($x) => $this->esEfectivo($x->code))->pluck('code');
            $tiene = fn (string $p) => $contra->contains(fn ($c) => str_starts_with($c, $p));
            $haciaLaSocia = $cab->referencia_tipo === 'purchase' && in_array($cab->referencia_id, $aLaSocia);

            foreach ($asiento as $x) {
                $resultado -= in_array($x->code[0], ['4', '5', '6'], true) ? $x->v : 0;
                if (str_starts_with($x->code, '1.2.01.07') && $cab->referencia_tipo === 'depreciacion') {
                    $depreciacion -= $x->v;
                }
            }

            // Método directo: solo lo que movió caja o bancos, y no una transferencia entre ellos.
            if ($efectivo != 0.0 && $contra->isNotEmpty()) {
                $clave = match (true) {
                    $haciaLaSocia || $tiene('1.1.02.04') => $efectivo < 0 ? '950215' : '950216',
                    $tiene('4')                          => '95010101',
                    $tiene('2.2') || $tiene('2.1.02.01') => $efectivo > 0 ? '950304' : '950305',
                    $tiene('3')                          => '950301',
                    $tiene('1.2')                        => $efectivo < 0 ? '950209' : '950208',
                    default                              => $efectivo < 0 ? '95010201' : '95010105',
                };
                $f[$clave] += $efectivo;
            }

            // Cambios en activos y pasivos de la operación: fuera lo que no es efectivo ni operación.
            $operacion = ! in_array($cab->referencia_tipo, self::SIN_EFECTIVO, true) && ! $haciaLaSocia
                && ! $tiene('1.2') && ! $tiene('1.1.02.04') && ! $tiene('2.2') && ! $tiene('3');
            if ($operacion) {
                foreach ($asiento as $x) {
                    $grupo = match (true) {
                        str_starts_with($x->code, '1.1.02.01'), str_starts_with($x->code, '1.1.02.02') => '9801',
                        str_starts_with($x->code, '1.1.03') => '9804',
                        str_starts_with($x->code, '1.1.05') => '9805',
                        str_starts_with($x->code, '2.1.01') => '9806',
                        str_starts_with($x->code, '2.1.02.03'), str_starts_with($x->code, '2.1.04') => '9807',
                        default => null,
                    };
                    if ($grupo) {
                        $cambios[$grupo] -= $x->v; // un activo que sube resta; un pasivo que sube suma
                    }
                }
            }
        }

        $r = fn ($v) => round($v, 2);
        $f = array_map($r, $f);
        $f['950101'] = $r($f['95010101'] + $f['95010105']);
        $f['950102'] = $f['95010201'];
        $f['9501']   = $r($f['950101'] + $f['950102']);
        $f['9502']   = $r($f['950209'] + $f['950208'] + $f['950215'] + $f['950216']);
        $f['9503']   = $r($f['950301'] + $f['950304'] + $f['950305']);
        $f['95']     = $r($f['9501'] + $f['9502'] + $f['9503']);
        $f['9504']   = 0.0;
        $f['9505']   = $f['95'];
        $f['9506']   = $this->efectivoAl($empresaId, "{$anio}-01-01", false);
        $f['9507']   = $r($f['9506'] + $f['9505']);

        // Conciliación: del resultado al flujo de operación.
        $f['96']   = $r($resultado);
        $f['9701'] = $r($depreciacion);
        foreach ($cambios as $k => $v) {
            $f[$k] = $r($v);
        }
        $f['98'] = $r(array_sum($cambios));
        $f['9711'] = $r($f['9501'] - $f['96'] - $f['9701'] - $f['98']);
        $f['97'] = $r($f['9701'] + $f['9711']);
        $f['9820'] = $r($f['96'] + $f['97'] + $f['98']);

        return $f;
    }

    private function esEfectivo(string $code): bool
    {
        return str_starts_with($code, '1.1.01');
    }

    /** Saldo de caja y bancos antes (o hasta) una fecha. */
    private function efectivoAl(int $empresaId, string $fecha, bool $incluida): float
    {
        return round((float) DB::table('journal_entry_lines as l')
            ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('account_plans as a', 'a.id', '=', 'l.account_plan_id')
            ->where('j.empresa_id', $empresaId)->where('j.status', 'confirmado')
            ->where('j.fecha', $incluida ? '<=' : '<', $fecha)
            ->where('a.code', 'like', '1.1.01%')
            ->sum(DB::raw('l.debe - l.haber')), 2);
    }
}
