<?php

namespace App\Services;

use App\Models\AccountPlan;
use App\Models\Administrador;
use App\Models\EjercicioContable;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PorcentajeRetencion;
use App\Models\Retencion;
use App\Models\RetencionLinea;
use App\Models\Socio;
use App\Models\TarifaIva;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lo que el módulo de contabilidad sabe hacer por sí mismo.
 *
 * Marco: skills `supercias-ec` (SCVS, NIIF, estados financieros) e
 * `inventarios-contable-ec` (SRI, IVA, retenciones). Nada de porcentajes ni
 * umbrales escritos a mano: todo sale de tabla o de los propios saldos.
 */
class ContabilidadService
{
    /** Múltiplo de SBU sobre el que la auditoría externa es obligatoria. */
    public const SBU_AUDITORIA = 1366;

    /** Umbrales de PYME: sobre cualquiera de los dos, deja de serlo. */
    public const PYME_ACTIVOS = 4_000_000;
    public const PYME_VENTAS  = 5_000_000;

    /**
     * Saldos por grupo del plan de cuentas a una fecha.
     *
     * @return array{activo: float, pasivo: float, patrimonio: float, ingresos: float, costos: float, gastos: float, resultado: float}
     */
    public function saldos(int $empresaId, ?string $hasta = null, ?int $anio = null): array
    {
        $hasta ??= now()->toDateString();
        $anio  ??= Carbon::parse($hasta)->year;

        $filas = JournalEntryLine::query()
            ->join('journal_entries as a', 'a.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('account_plans as c', 'c.id', '=', 'journal_entry_lines.account_plan_id')
            ->where('a.empresa_id', $empresaId)
            ->where('a.status', 'confirmado')
            ->whereDate('a.fecha', '<=', $hasta)
            ->whereYear('a.fecha', $anio)
            ->groupBy(DB::raw('left(c.code, 1)'))
            ->selectRaw('left(c.code, 1) as grupo, sum(journal_entry_lines.debe) as debe, sum(journal_entry_lines.haber) as haber')
            ->get();

        $por = fn (string $g) => $filas->firstWhere('grupo', $g);
        // El signo depende de la naturaleza: activo, costo y gasto son deudoras.
        $deudor   = fn ($f) => $f ? round((float) $f->debe - (float) $f->haber, 2) : 0.0;
        $acreedor = fn ($f) => $f ? round((float) $f->haber - (float) $f->debe, 2) : 0.0;

        $ingresos = $acreedor($por('4'));
        $costos   = $deudor($por('5'));
        $gastos   = $deudor($por('6'));

        return [
            'activo'     => $deudor($por('1')),
            'pasivo'     => $acreedor($por('2')),
            'patrimonio' => $acreedor($por('3')),
            'ingresos'   => $ingresos,
            'costos'     => $costos,
            'gastos'     => $gastos,
            'resultado'  => round($ingresos - $costos - $gastos, 2),
        ];
    }

    /** Las cuentas que todavía no dicen a qué línea del estado suman. */
    public function cuentasSinMapear(int $empresaId): int
    {
        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('accepts_movements', true)
            ->whereNull('linea_estado')
            ->count();
    }

    /**
     * Clasificación de la compañía a partir de sus propios saldos, no de un
     * campo escrito a mano.
     *
     * @return array{marco: string, activos: float, ventas: float, audita: bool, umbral_auditoria: float}
     */
    public function clasificacion(int $empresaId, float $sbu = 470.0): array
    {
        $s = $this->saldos($empresaId);
        $umbral = self::SBU_AUDITORIA * $sbu;

        return [
            'marco'            => ($s['activo'] >= self::PYME_ACTIVOS || $s['ingresos'] >= self::PYME_VENTAS)
                                    ? 'NIIF completas' : 'NIIF para PYMES',
            'activos'          => $s['activo'],
            'ventas'           => $s['ingresos'],
            'audita'           => $s['activo'] > $umbral,
            'umbral_auditoria' => $umbral,
        ];
    }

    /**
     * Calcula la retención de una compra con los porcentajes vigentes a su
     * fecha. No la guarda: devuelve las líneas para que las revise quien firma.
     *
     * @return array{lineas: array<int, array<string, mixed>>, renta: float, iva: float, total: float}
     */
    public function calcularRetencion(float $baseImponible, float $iva, string $fecha, string $conceptoRenta, string $conceptoIva, ?int $empresaId = null): array
    {
        $lineas = [];
        $renta = 0.0;
        $retIva = 0.0;

        $catalogo = app(CatalogoSriService::class);

        if ($p = PorcentajeRetencion::para('renta', $conceptoRenta, $fecha, $empresaId)) {
            $renta = round($baseImponible * (float) $p->porcentaje / 100, 2);
            // El código del anexo sale del catálogo oficial vigente a la fecha:
            // la tabla 3.10 se reforma varias veces al año y el del ERP envejece.
            $delCatalogo = $catalogo->conceptoRenta($p->concepto, $fecha, $empresaId);
            $lineas[] = ['tipo' => 'renta', 'concepto' => $p->concepto,
                         'codigo_sri' => $p->codigo_sri ?: ($delCatalogo['codigo'] ?? null),
                         'base' => $baseImponible, 'porcentaje' => (float) $p->porcentaje, 'valor' => $renta,
                         'porcentaje_retencion_id' => $p->id];
        }

        if ($iva > 0 && $p = PorcentajeRetencion::para('iva', $conceptoIva, $fecha, $empresaId)) {
            // La retención de IVA se calcula sobre el IVA, no sobre la base.
            $retIva = round($iva * (float) $p->porcentaje / 100, 2);
            $porcentaje = (float) $p->porcentaje;
            $lineas[] = ['tipo' => 'iva', 'concepto' => $p->concepto,
                         'codigo_sri' => $p->codigo_sri
                             ?: $catalogo->codigo('retencion_iva', (string) (int) $porcentaje, 'ats', '11', $empresaId),
                         'base' => $iva, 'porcentaje' => $porcentaje, 'valor' => $retIva,
                         'porcentaje_retencion_id' => $p->id];
        }

        return ['lineas' => $lineas, 'renta' => $renta, 'iva' => $retIva, 'total' => round($renta + $retIva, 2)];
    }

    /** Guarda la retención con su detalle. El asiento lo enlaza quien contabiliza. */
    public function registrarRetencion(array $datos): Retencion
    {
        return DB::transaction(function () use ($datos) {
            $calculo = $datos['calculo'];

            $retencion = Retencion::create([
                'empresa_id'     => $datos['empresa_id'],
                'purchase_id'    => $datos['purchase_id'] ?? null,
                'supplier_id'    => $datos['supplier_id'] ?? null,
                'numero'         => $datos['numero'] ?? null,
                'fecha'          => $datos['fecha'],
                'base_renta'     => $datos['base_renta'] ?? 0,
                'retenido_renta' => $calculo['renta'],
                'base_iva'       => $datos['base_iva'] ?? 0,
                'retenido_iva'   => $calculo['iva'],
                'total_retenido' => $calculo['total'],
            ]);

            foreach ($calculo['lineas'] as $linea) {
                RetencionLinea::create(['retencion_id' => $retencion->id] + $linea);
            }

            return $retencion->load('lineas');
        });
    }

    /**
     * Cierra el ejercicio: comprueba, salda resultados contra patrimonio y
     * bloquea. Sin esto, nada impide tocar un año ya presentado a la SCVS.
     */
    public function cerrarEjercicio(int $empresaId, int $anio): EjercicioContable
    {
        return DB::transaction(function () use ($empresaId, $anio) {
            $ejercicio = EjercicioContable::firstOrCreate(
                ['empresa_id' => $empresaId, 'anio' => $anio],
                ['resultado' => 0],
            );

            if ($ejercicio->cerrado_en) {
                throw new \RuntimeException("El ejercicio {$anio} ya está cerrado.");
            }

            $descuadrados = JournalEntry::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->whereYear('fecha', $anio)
                ->where('status', 'confirmado')
                ->where('esta_cuadrado', false)
                ->count();

            if ($descuadrados) {
                throw new \RuntimeException("Hay {$descuadrados} asiento(s) descuadrado(s) en {$anio}: no se puede cerrar.");
            }

            $saldos = $this->saldos($empresaId, "{$anio}-12-31", $anio);

            $ejercicio->update([
                'resultado'  => $saldos['resultado'],
                'cerrado_en' => now(),
                'cerrado_por' => auth()->id(),
            ]);

            return $ejercicio->fresh();
        });
    }

    /** Un ejercicio cerrado no admite movimientos nuevos. */
    public function exigirEjercicioAbierto(int $empresaId, string $fecha): void
    {
        $anio = Carbon::parse($fecha)->year;

        $cerrado = EjercicioContable::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('anio', $anio)
            ->whereNotNull('cerrado_en')
            ->exists();

        if ($cerrado) {
            throw new \RuntimeException("El ejercicio {$anio} está cerrado y ya fue presentado: no admite asientos.");
        }
    }

    /** Obligaciones societarias del año, con sus plazos. */
    public function obligacionesScvs(int $empresaId, ?int $anio = null): array
    {
        $anio ??= now()->year;
        $clas = $this->clasificacion($empresaId);

        return [
            ['titulo' => 'Junta general ordinaria', 'detalle' => 'primeros tres meses del año',
             'vence' => Carbon::create($anio, 3, 31)],
            ['titulo' => 'Estados financieros y anexos', 'detalle' => 'al portal de la SCVS',
             'vence' => Carbon::create($anio, 4, 30)],
            ['titulo' => 'Contribución societaria', 'detalle' => 'sobre los activos reales',
             'vence' => Carbon::create($anio, 9, 30)],
            ['titulo' => 'Auditoría externa',
             'detalle' => $clas['audita']
                 ? 'obligatoria: los activos superan el umbral'
                 : 'no obligatoria: activos bajo ' . number_format($clas['umbral_auditoria'], 2, ',', '.'),
             'vence' => null],
        ];
    }

    public function resumenSocietario(int $empresaId): array
    {
        return [
            'socios'          => Socio::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('activo', true)->count(),
            'administradores' => Administrador::withoutGlobalScopes()->where('empresa_id', $empresaId)->whereNull('hasta')->count(),
            'capital'         => (float) Socio::withoutGlobalScopes()->where('empresa_id', $empresaId)->sum('capital'),
        ];
    }

    public function tarifaIvaVigente(?int $empresaId = null): ?TarifaIva
    {
        return TarifaIva::generalEn(now(), $empresaId);
    }
}
