<?php

namespace App\Services;

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
                $valor = $f->nature === 'deudora'
                    ? (float) $f->debe - (float) $f->haber
                    : (float) $f->haber - (float) $f->debe;

                $acc[$f->codigo] = round(($acc[$f->codigo] ?? 0) + $valor, 2);

                return $acc;
            }, []);
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

        $actual   = $this->consolidar($this->saldosPorCodigo($empresaId, $anio), $lineas);
        $anterior = $this->consolidar($this->saldosPorCodigo($empresaId, $anio - 1), $lineas);

        return $lineas->map(fn ($l) => [
            'codigo'      => $l->codigo,
            'nombre'      => $l->nombre,
            'nivel'       => (int) $l->nivel,
            'columna'     => $l->columna,
            'actual'      => round($actual[$l->codigo] ?? 0, 2),
            'anterior'    => round($anterior[$l->codigo] ?? 0, 2),
            'tiene_valor' => abs($actual[$l->codigo] ?? 0) > 0.001 || abs($anterior[$l->codigo] ?? 0) > 0.001,
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

        // Los tres movimientos que salen del libro diario.
        $saldoFinal   = $this->saldosDeConceptos($empresaId, $anio, $conceptos);
        $saldoInicial = $this->saldosDeConceptos($empresaId, $anio - 1, $conceptos);
        $resultado    = app(ContabilidadService::class)->saldos($empresaId, "{$anio}-12-31", $anio)['resultado'];

        $derivadas = ['99', '9901', '990101', '990210'];
        $celdas = [];

        foreach ($conceptos as $c) {
            foreach ($columnas as $col) {
                $celdas[$col['codigo']][$c['codigo']] = match ($col['codigo']) {
                    '99'      => $saldoFinal[$c['codigo']] ?? 0.0,
                    '9901', '990101' => $saldoInicial[$c['codigo']] ?? 0.0,
                    // El resultado del año va a la línea de ganancias acumuladas.
                    '990210'  => in_array($c['codigo'], ['30601', '30602'], true) ? round($resultado, 2) : 0.0,
                    default   => 0.0,
                };
            }
        }

        return ['conceptos' => $conceptos, 'columnas' => $columnas,
                'celdas' => $celdas, 'derivadas' => $derivadas];
    }

    /** Saldo de cada concepto patrimonial en un ejercicio. */
    private function saldosDeConceptos(int $empresaId, int $anio, array $conceptos): array
    {
        $saldos = $this->saldosPorCodigo($empresaId, $anio);
        $totales = [];

        foreach ($conceptos as $c) {
            $suma = 0.0;

            foreach ($saldos as $codigo => $valor) {
                if (str_starts_with((string) $codigo, $c['codigo'])) {
                    $suma += $valor;
                }
            }

            $totales[$c['codigo']] = round($suma, 2);
        }

        return $totales;
    }

    /** Contenido del archivo de un estado, listo para guardar como .txt */
    public function archivo(int $empresaId, int $anio, string $estado): string
    {
        $lineas = CatalogoSupercias::where('estado', $estado)->orderBy('orden')->get();
        $totales = $this->consolidar($this->saldosPorCodigo($empresaId, $anio), $lineas);

        $salida = [];
        foreach ($lineas as $linea) {
            $valor = number_format($totales[$linea->codigo] ?? 0, 2, '.', '');
            $salida[] = $linea->columna
                ? "{$linea->columna} {$linea->codigo} {$valor}"
                : "{$linea->codigo} {$valor}";
        }

        return implode("\n", $salida) . "\n";
    }

    /** Los cuatro archivos del juego completo. */
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
        $saldos = $this->saldosPorCodigo($empresaId, $anio);
        $resumen = [];

        foreach (CatalogoSupercias::ESTADOS as $estado => $etiqueta) {
            $lineas = CatalogoSupercias::where('estado', $estado)->orderBy('orden')->get();
            $totales = $this->consolidar($saldos, $lineas);
            $conValor = count(array_filter($totales, fn ($v) => abs($v) > 0.001));

            $resumen[$estado] = [
                'etiqueta' => $etiqueta,
                'archivo'  => CatalogoSupercias::ARCHIVOS[$estado],
                'lineas'   => $lineas->count(),
                'conValor' => $conValor,
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
