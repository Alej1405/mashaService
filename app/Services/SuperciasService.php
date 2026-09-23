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
    private function consolidar(array $saldos, Collection $lineas): array
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

    /** Cuentas de la empresa que todavía no apuntan al catálogo. */
    public function cuentasSinCodigo(int $empresaId): int
    {
        return \App\Models\AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('accepts_movements', true)
            ->whereNull('codigo_supercias')
            ->count();
    }
}
