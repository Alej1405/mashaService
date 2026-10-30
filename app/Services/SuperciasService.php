<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Models\CatalogoSupercias;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

/**
 * Los archivos que se suben al portal de la Superintendencia.
 *
 * Formato verificado sobre los TXT oficiales: una línea por código del
 * catálogo, `CODIGO VALOR`, con dos decimales y punto. El estado de cambios en
 * el patrimonio lleva columna antes del código: `99 301 0.00`.
 *
 * El valor sale de los asientos de la empresa agrupados por el código de
 * Supercías que declara cada cuenta del plan. Una cuenta sin código no suma:
 * por eso el módulo avisa cuántas faltan.
 */
class SuperciasService
{
    /** Saldo por código de Supercías en un ejercicio. */
    public function saldosPorCodigo(int $empresaId, int $anio): array
    {
        return JournalEntryLine::query()
            ->join('journal_entries as a', 'a.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('account_plans as c', 'c.id', '=', 'journal_entry_lines.account_plan_id')
            ->where('a.empresa_id', $empresaId)
            ->where('a.status', 'confirmado')
            ->whereYear('a.fecha', $anio)
            ->whereNotNull('c.codigo_supercias')
            ->groupBy('c.codigo_supercias', 'c.nature')
            ->selectRaw('c.codigo_supercias as codigo, c.nature, sum(journal_entry_lines.debe) as debe, sum(journal_entry_lines.haber) as haber')
            ->get()
            ->reduce(function (array $acc, $f) {
                // El signo lo pone la sección del catálogo, no la naturaleza de la cuenta:
                // una correctora (depreciación acumulada, pérdidas) resta en su sección.
                $valor = in_array(((string) $f->codigo)[0], ['1', '5'], true)
                    ? (float) $f->debe - (float) $f->haber
                    : (float) $f->haber - (float) $f->debe;

                $acc[$f->codigo] = round(($acc[$f->codigo] ?? 0) + $valor, 2);

                return $acc;
            }, []);
    }

    /**
     * El balance lleva el resultado del ejercicio en el patrimonio (307). Mientras no
     * se cierra, sale de ingresos menos costos y gastos; cerrado, esas cuentas quedan
     * en cero y el resultado ya está en 3.7, así que no se cuenta dos veces.
     */
    private function conResultado(array $saldos): array
    {
        $resultado = 0.0;
        foreach ($saldos as $codigo => $valor) {
            $resultado += match (((string) $codigo)[0]) { '4' => $valor, '5' => -$valor, default => 0 };
        }

        $codigo = $resultado >= 0 ? '30701' : '30702';
        $saldos[$codigo] = round(($saldos[$codigo] ?? 0) + $resultado, 2);

        return $saldos;
    }

    /**
     * El costo de ventas con la fórmula de inventarios que pide la Superintendencia:
     * inventario inicial + compras − inventario final, por clase. El inventario final de
     * cada clase es el mismo del balance; así lo cruza la Superintendencia (1010301 con 5010108).
     *
     * El producto terminado que entró como saldo de cuadre es inventario que ya existía:
     * suma al inicial.
     */
    private function conCostoPorInventarios(int $empresaId, int $anio, array $saldos): array
    {
        $saldos = array_filter($saldos, fn ($c) => ! str_starts_with((string) $c, '50101'), ARRAY_FILTER_USE_KEY);

        $mov = fn (string $cuenta, string $condicion) => (float) DB::table('journal_entry_lines as l')
            ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('account_plans as a', 'a.id', '=', 'l.account_plan_id')
            ->where('j.empresa_id', $empresaId)->where('j.status', 'confirmado')->where('a.code', $cuenta)
            ->whereRaw($condicion, [$anio])
            ->sum(DB::raw('l.debe - l.haber'));
        $antes  = fn (string $c) => $mov($c, 'extract(year from j.fecha) < ?');
        $alFin  = fn (string $c) => $mov($c, 'extract(year from j.fecha) <= ?');
        $entra  = fn (string $c, array $tipos) => (float) DB::table('journal_entry_lines as l')
            ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('account_plans as a', 'a.id', '=', 'l.account_plan_id')
            ->where('j.empresa_id', $empresaId)->where('j.status', 'confirmado')->where('a.code', $c)
            ->whereYear('j.fecha', $anio)->whereIn('j.referencia_tipo', $tipos)->sum('l.debe');

        // Materia prima: inicial + compras locales − final.
        $saldos['5010105'] = round($antes('1.1.03.02'), 2);
        $saldos['5010106'] = round($entra('1.1.03.02', ['purchase']), 2);
        $saldos['5010108'] = round(-$alFin('1.1.03.02'), 2);
        // Producto terminado: inicial (con el saldo de cuadre) − final.
        $saldos['5010111'] = round($antes('1.1.03.04') + $entra('1.1.03.04', ['saldo_cuadre']), 2);
        $saldos['5010112'] = round(-$alFin('1.1.03.04'), 2);

        return $saldos;
    }

    /**
     * La cadena del resultado (600 → 707 → 801). Con pérdida no hay participación ni
     * impuesto causado; 707 es el mismo resultado que el balance lleva en 30701/30702.
     */
    private function conResultadoDelPeriodo(array $totales, array $saldos): array
    {
        $resultado = 0.0;
        foreach ($saldos as $codigo => $valor) {
            $resultado += match (((string) $codigo)[0]) { '4' => $valor, '5' => -$valor, default => 0 };
        }
        $resultado = round($resultado, 2);
        $participacion = $resultado > 0 ? round($resultado * 0.15, 2) : 0.0;

        // El portal cruza 402 = 401 − 501; no sale de ninguna cuenta, se calcula.
        $totales['402'] = round(($totales['401'] ?? 0) - ($totales['501'] ?? 0), 2);
        $totales['600'] = $resultado;
        $totales['601'] = $participacion;
        $totales['602'] = $totales['604'] = $totales['607'] = round($resultado - $participacion, 2);
        $totales['707'] = $totales['801'] = $totales['607'];
        // La Superintendencia pide la parte de los propietarios siempre en positivo, aun con pérdida.
        $totales['80101'] = abs($totales['801']);

        return $totales;
    }

    /**
     * Los saldos de las cuentas hijas suben a sus padres: el catálogo es
     * jerárquico y el portal espera los totales llenos.
     */
    public function consolidar(array $saldos, Collection $lineas): array
    {
        $totales = [];

        foreach ($saldos as $codigo => $valor) {
            // 1010101 alimenta a 10101, 101 y 1.
            foreach ($lineas as $linea) {
                if (str_starts_with($codigo, $linea->codigo)) {
                    $totales[$linea->codigo] = round(($totales[$linea->codigo] ?? 0) + $valor, 2);
                }
            }
        }

        return $totales;
    }

    /**
     * Un estado financiero completo, como se presenta y como se sube.
     *
     * Devuelve las líneas del catálogo con su saldo del ejercicio y el del
     * anterior. La pantalla y el archivo .txt del portal salen de aquí: si
     * saliesen de dos sitios, un día dirían cosas distintas.
     *
     * @return array<int, array{codigo: string, nombre: string, nivel: int,
     *                          actual: float, anterior: float, tiene_valor: bool}>
     */
    public function estado(int $empresaId, int $anio, string $estado): array
    {
        $lineas = CatalogoSupercias::where('estado', $estado)->orderBy('orden')->get();

        $saldos = fn (int $a) => match ($estado) {
            'situacion_financiera' => $this->conResultado($this->saldosPorCodigo($empresaId, $a)),
            'resultado_integral'   => $this->conCostoPorInventarios($empresaId, $a, $this->saldosPorCodigo($empresaId, $a)),
            default                => $this->saldosPorCodigo($empresaId, $a),
        };

        // El flujo de efectivo y los cambios en el patrimonio no salen de saldos consolidados.
        $valores = fn (int $a) => match ($estado) {
            'flujo_efectivo'     => app(FlujoEfectivoService::class)->calcular($empresaId, $a),
            'cambios_patrimonio' => $this->cambiosEnPatrimonio($empresaId, $a)['celdas'],
            'resultado_integral' => $this->conResultadoDelPeriodo($this->consolidar($saldos($a), $lineas), $saldos($a)),
            default              => $this->consolidar($saldos($a), $lineas),
        };

        $actual   = $valores($anio);
        $anterior = $valores($anio - 1);

        // En la matriz del patrimonio cada línea es una celda: columna × concepto.
        $de = fn (array $v, $l) => $estado === 'cambios_patrimonio' ? ($v[$l->columna][$l->codigo] ?? 0) : ($v[$l->codigo] ?? 0);

        return $lineas->map(fn ($l) => [
            'codigo'      => $l->codigo,
            'nombre'      => $l->nombre,
            'nivel'       => (int) $l->nivel,
            'columna'     => $l->columna,
            'actual'      => round($de($actual, $l), 2),
            'anterior'    => round($de($anterior, $l), 2),
            'tiene_valor' => abs($de($actual, $l)) > 0.001 || abs($de($anterior, $l)) > 0.001,
        ])->all();
    }

    /**
     * El estado de cambios en el patrimonio, como la matriz que es.
     *
     * Sus líneas son 20 conceptos patrimoniales × 16 movimientos. Consolidar
     * por prefijo como en los demás estados daría el mismo número en las
     * dieciséis celdas de cada concepto, que es lo que pasaba.
     *
     * De los dieciséis movimientos, el sistema sabe calcular tres sin ayuda:
     * el saldo al cierre del año anterior, el resultado del ejercicio y el
     * saldo final. Los demás —aumentos de capital, dividendos, transferencias
     * entre cuentas patrimoniales— son decisiones societarias que no se
     * deducen del libro diario: salen en cero y así se dice.
     *
     * @return array{conceptos: array, columnas: array, celdas: array, derivadas: array}
     */
    public function cambiosEnPatrimonio(int $empresaId, int $anio): array
    {
        $lineas = CatalogoSupercias::where('estado', 'cambios_patrimonio')->orderBy('orden')->get();

        $conceptos = $lineas->unique('codigo')
            ->map(fn ($l) => ['codigo' => $l->codigo, 'nombre' => $l->nombre])
            ->values()->all();

        $columnas = $lineas->unique('columna')
            ->map(fn ($l) => ['codigo' => $l->columna, 'nombre' => $l->nombre_columna ?: $l->columna])
            ->values()->all();

        // Del libro diario salen los saldos al inicio y al final de cada concepto (con el
        // resultado del año en 307) y lo que cambió en el año, repartido por su origen:
        // capital, aportes, resultado del ejercicio y otros cambios. Inicial + cambios = final.
        $final   = $this->conResultado($this->saldosPorCodigo($empresaId, $anio));
        $inicial = $this->conResultado($this->saldosPorCodigo($empresaId, $anio - 1));
        $columnaDe = fn (string $codigo) => match (true) {
            str_starts_with($codigo, '307') => '990210',
            str_starts_with($codigo, '302') => '990202',
            str_starts_with($codigo, '301') => '990201',
            default                         => '990209',
        };

        $derivadas = ['99', '9901', '990101', '9902', '990201', '990202', '990209', '990210'];
        $celdas = [];

        foreach ($conceptos as $c) {
            $suma = array_fill_keys($derivadas, 0.0);
            foreach (array_unique(array_merge(array_keys($final), array_keys($inicial))) as $codigo) {
                $codigo = (string) $codigo;
                if ($codigo[0] !== '3' || ! str_starts_with($codigo, $c['codigo'])) {
                    continue;
                }
                $fin = $final[$codigo] ?? 0.0;
                $ini = $inicial[$codigo] ?? 0.0;
                $suma['99'] += $fin;
                $suma['990101'] += $ini;
                $suma['9901'] += $ini;
                $suma[$columnaDe($codigo)] += $fin - $ini;
                $suma['9902'] += $fin - $ini;
            }

            foreach ($columnas as $col) {
                $celdas[$col['codigo']][$c['codigo']] = round($suma[$col['codigo']] ?? 0.0, 2);
            }
        }

        return ['conceptos' => $conceptos, 'columnas' => $columnas,
                'celdas' => $celdas, 'derivadas' => $derivadas];
    }

    /** Contenido del archivo de un estado, listo para guardar como .txt */
    public function archivo(int $empresaId, int $anio, string $estado): string
    {
        // El archivo sale de lo mismo que la pantalla: si saliera de otro cálculo, un día dirían cosas distintas.
        $salida = [];
        foreach ($this->estado($empresaId, $anio, $estado) as $linea) {
            $valor = number_format($linea['actual'], 2, '.', '');
            $salida[] = $linea['columna']
                ? "{$linea['columna']} {$linea['codigo']} {$valor}"
                : "{$linea['codigo']} {$valor}";
        }

        // Así vienen los formatos oficiales: el de cambios en el patrimonio en LF, los otros tres en CRLF.
        $salto = $estado === 'cambios_patrimonio' ? "\n" : "\r\n";

        return implode($salto, $salida) . $salto;
    }

    public function juegoCompleto(int $empresaId, int $anio): array
    {
        $archivos = [];

        foreach (CatalogoSupercias::ARCHIVOS as $estado => $nombre) {
            $archivos[$nombre] = $this->archivo($empresaId, $anio, $estado);
        }

        return $archivos;
    }

    /** Cuántas líneas del archivo salen con valor, para no subir un archivo en ceros. */
    public function resumen(int $empresaId, int $anio): array
    {
        $resumen = [];

        // Cuenta lo mismo que sale en el archivo: las líneas del estado con valor en el año.
        foreach (CatalogoSupercias::ESTADOS as $estado => $etiqueta) {
            $lineas = $this->estado($empresaId, $anio, $estado);

            $resumen[$estado] = [
                'etiqueta' => $etiqueta,
                'archivo'  => CatalogoSupercias::ARCHIVOS[$estado],
                'lineas'   => count($lineas),
                'conValor' => count(array_filter($lineas, fn ($l) => abs($l['actual']) > 0.001)),
            ];
        }

        return $resumen;
    }

    /**
     * Cuentas que todavía no apuntan al catálogo.
     *
     * Con el mapeo automático esto debería ser siempre cero: si no lo es, algo
     * falló al crear la cuenta. Lo que sí hay que mirar es cuántas están sin
     * confirmar, y de eso responde `MapeoSuperciasService::porRevisar()`, que
     * es el único contador de esta idea en todo el sistema.
     */
    public function cuentasSinCodigo(int $empresaId): int
    {
        return \App\Models\AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('accepts_movements', true)
            ->whereNull('codigo_supercias')
            ->count();
    }
}
