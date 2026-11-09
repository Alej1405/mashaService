<?php

namespace App\Services;

use App\Models\AccountPlan;
use App\Models\Administrador;
use App\Models\EjercicioContable;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PeriodoContable;
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
     * Las líneas que cuentan para el ejercicio, hasta una fecha.
     *
     * Activo, pasivo y patrimonio (1, 2, 3) se acumulan desde el inicio: el saldo de
     * diciembre es el de enero siguiente. Ingresos, costos y gastos (4, 5, 6) son solo
     * del año. El asiento de cierre del propio año no entra: si entrara, dejaría los
     * resultados en cero y el estado de resultados ya presentado daría distinto.
     * La consulta debe unir journal_entries como «a» y account_plans como «c».
     */
    public function delEjercicio($query, int $anio, string $hasta)
    {
        return $query->whereDate('a.fecha', '<=', $hasta)
            ->where(fn ($q) => $q
                ->where(fn ($b) => $b->whereRaw("left(c.code, 1) in ('1', '2', '3')")
                    ->where(fn ($x) => $x->where('a.tipo', '<>', 'cierre')->orWhereYear('a.fecha', '<>', $anio)))
                ->orWhere(fn ($r) => $r->whereRaw("left(c.code, 1) in ('4', '5', '6')")
                    ->whereYear('a.fecha', $anio)
                    ->where('a.tipo', '<>', 'cierre')));
    }

    /**
     * Saldos por grupo del plan de cuentas a una fecha.
     *
     * @return array{activo: float, pasivo: float, patrimonio: float, ingresos: float, costos: float, gastos: float, resultado: float}
     */
    public function saldos(int $empresaId, ?string $hasta = null, ?int $anio = null): array
    {
        $hasta ??= now()->toDateString();
        $anio  ??= Carbon::parse($hasta)->year;

        $filas = $this->delEjercicio(
            JournalEntryLine::query()
                ->join('journal_entries as a', 'a.id', '=', 'journal_entry_lines.journal_entry_id')
                ->join('account_plans as c', 'c.id', '=', 'journal_entry_lines.account_plan_id')
                ->where('a.empresa_id', $empresaId)
                ->where('a.status', 'confirmado'),
            $anio, $hasta)
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
     * Cierra el ejercicio contable.
     *
     * Comprueba que el año esté listo (meses cerrados, nada descuadrado ni en
     * borrador), lleva el resultado al patrimonio con el asiento de cierre al 31/12,
     * lo pasa a resultados acumulados el 01/01 siguiente y bloquea el año.
     * Marco: skill `contabilidad-ec`, cierre de ejercicio.
     */
    public function cerrarEjercicio(int $empresaId, int $anio): EjercicioContable
    {
        return DB::transaction(function () use ($empresaId, $anio) {
            $ejercicio = EjercicioContable::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $empresaId, 'anio' => $anio],
                ['resultado' => 0],
            );

            if ($ejercicio->cerrado_en) {
                throw new \RuntimeException("El ejercicio {$anio} ya está cerrado.");
            }

            $delAnio = fn () => JournalEntry::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)->whereYear('fecha', $anio);

            if ($n = $delAnio()->where('status', 'confirmado')->where('esta_cuadrado', false)->count()) {
                throw new \RuntimeException("Hay {$n} asiento(s) descuadrado(s) en {$anio}: no se puede cerrar.");
            }

            if ($n = $delAnio()->where('status', 'borrador')->count()) {
                throw new \RuntimeException("Hay {$n} asiento(s) en borrador en {$anio}: confírmalos o anúlalos antes de cerrar.");
            }

            if ($abiertos = $this->mesesAbiertos($empresaId, $anio)) {
                throw new \RuntimeException('Faltan meses por cerrar: ' . implode(', ', $abiertos) . '.');
            }

            $resultado = $this->saldos($empresaId, "{$anio}-12-31", $anio)['resultado'];
            $asiento   = $this->asientoDeCierre($empresaId, $anio, $ejercicio->id);
            $this->resultadoAAcumulados($empresaId, $anio, $resultado, $ejercicio->id);

            $ejercicio->update([
                'resultado'         => $resultado,
                'asiento_cierre_id' => $asiento?->id,
                'cerrado_en'        => now(),
                'cerrado_por'       => auth()->id(),
            ]);

            return $ejercicio->fresh();
        });
    }

    /**
     * Primer mes del año en que la empresa tiene actividad: su primer asiento o su
     * primer 104. Antes de eso no hay nada que cerrar. Devuelve 13 si el año es
     * anterior a toda actividad.
     */
    public function primerMesConActividad(int $empresaId, int $anio): int
    {
        $primero = collect([
            JournalEntry::withoutGlobalScopes()->where('empresa_id', $empresaId)->min('fecha'),
            \App\Models\Declaracion::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('tipo', 'f104')
                ->selectRaw('min(make_date(anio, mes, 1)) as f')->value('f'),
        ])->filter()->map(fn ($f) => Carbon::parse($f))->min();

        return match (true) {
            ! $primero || $primero->year < $anio => 1,
            $primero->year > $anio               => 13,
            default                              => $primero->month,
        };
    }

    /** Meses con actividad del año que siguen abiertos, como «08/2025». */
    public function mesesAbiertos(int $empresaId, int $anio): array
    {
        $cerrados = PeriodoContable::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)->where('anio', $anio)
            ->whereNotNull('cerrado_en')->pluck('mes')->map(fn ($m) => (int) $m)->all();

        return collect(range($this->primerMesConActividad($empresaId, $anio), 12))
            ->reject(fn ($m) => $m > 12 || in_array($m, $cerrados, true))
            ->map(fn ($m) => sprintf('%02d/%d', $m, $anio))
            ->values()->all();
    }

    /**
     * Asiento de cierre al 31/12: salda cada cuenta de ingresos, costos y gastos y
     * lleva la diferencia a la 3.7.01 (ganancia) o a la 3.7.02 (pérdida).
     */
    private function asientoDeCierre(int $empresaId, int $anio, int $ejercicioId): ?JournalEntry
    {
        $cuentas = JournalEntryLine::query()
            ->join('journal_entries as a', 'a.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('account_plans as c', 'c.id', '=', 'journal_entry_lines.account_plan_id')
            ->where('a.empresa_id', $empresaId)
            ->where('a.status', 'confirmado')
            ->whereYear('a.fecha', $anio)
            ->where('a.tipo', '<>', 'cierre')
            ->whereRaw("left(c.code, 1) in ('4', '5', '6')")
            ->groupBy('journal_entry_lines.account_plan_id')
            ->selectRaw('journal_entry_lines.account_plan_id as cuenta, sum(journal_entry_lines.debe) as debe, sum(journal_entry_lines.haber) as haber')
            ->get();

        // Cada cuenta se salda con su saldo al lado contrario.
        $lineas = [];
        $resultado = '0.00';                       // haber − debe: positivo es ganancia
        foreach ($cuentas as $c) {
            $saldo = bcsub((string) $c->haber, (string) $c->debe, 2);
            if (bccomp($saldo, '0', 2) === 0) {
                continue;
            }
            $lineas[] = bccomp($saldo, '0', 2) > 0
                ? [$c->cuenta, $saldo, '0.00']
                : [$c->cuenta, '0.00', bcmul($saldo, '-1', 2)];
            $resultado = bcadd($resultado, $saldo, 2);
        }

        if (! $lineas) {
            return null;
        }

        $ganancia = bccomp($resultado, '0', 2) >= 0;
        $lineas[] = $ganancia
            ? [$this->cuentaDelPlan($empresaId, '3.7.01')->id, '0.00', $resultado]
            : [$this->cuentaDelPlan($empresaId, '3.7.02')->id, bcmul($resultado, '-1', 2), '0.00'];

        return $this->asientoAutomatico($empresaId, "{$anio}-12-31", 'cierre', $ejercicioId,
            "Cierre del ejercicio {$anio}: resultados a patrimonio", $lineas);
    }

    /**
     * El 01/01 siguiente, el resultado del año pasa de «resultados del ejercicio»
     * (3.7) a «resultados acumulados» (3.6). Es de tipo apertura: pertenece al año
     * nuevo y sí cuenta en su balance.
     */
    private function resultadoAAcumulados(int $empresaId, int $anio, float $resultado, int $ejercicioId): void
    {
        $valor = number_format(abs($resultado), 2, '.', '');
        if (bccomp($valor, '0', 2) === 0) {
            return;
        }

        $lineas = $resultado > 0
            ? [[$this->cuentaDelPlan($empresaId, '3.7.01')->id, $valor, '0.00'],
               [$this->cuentaDelPlan($empresaId, '3.6.01')->id, '0.00', $valor]]
            : [[$this->cuentaDelPlan($empresaId, '3.6.02')->id, $valor, '0.00'],
               [$this->cuentaDelPlan($empresaId, '3.7.02')->id, '0.00', $valor]];

        $this->asientoAutomatico($empresaId, ($anio + 1) . '-01-01', 'apertura', $ejercicioId,
            "Resultado del ejercicio {$anio} a resultados acumulados", $lineas);
    }

    /** Cuenta del plan estándar de la empresa por su código, o un error que dice cuál falta. */
    private function cuentaDelPlan(int $empresaId, string $codigo): AccountPlan
    {
        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)->where('code', $codigo)->where('accepts_movements', true)
            ->first()
            ?? throw new \RuntimeException("Falta la cuenta {$codigo} en el plan de cuentas de la empresa.");
    }

    /**
     * Guarda un asiento del cierre ya confirmado. Las líneas son [cuenta, debe, haber]
     * en texto con dos decimales; el cuadre se calcula, no se declara.
     */
    private function asientoAutomatico(int $empresaId, string $fecha, string $tipo, int $ejercicioId, string $descripcion, array $lineas): JournalEntry
    {
        $debe  = array_reduce($lineas, fn ($t, $l) => bcadd($t, $l[1], 2), '0.00');
        $haber = array_reduce($lineas, fn ($t, $l) => bcadd($t, $l[2], 2), '0.00');

        if (bccomp($debe, $haber, 2) !== 0) {
            throw new \RuntimeException("El asiento «{$descripcion}» no cuadra: debe {$debe}, haber {$haber}.");
        }

        $asiento = JournalEntry::create([
            'empresa_id'      => $empresaId,
            'fecha'           => $fecha,
            'descripcion'     => $descripcion,
            'tipo'            => $tipo,
            'origen'          => 'automatico',
            'referencia_tipo' => 'ejercicio',
            'referencia_id'   => $ejercicioId,
            'status'          => 'confirmado',
            'total_debe'      => $debe,
            'total_haber'     => $haber,
            'esta_cuadrado'   => true,
            'confirmado_por'  => auth()->id(),
            'confirmado_at'   => now(),
        ]);

        foreach ($lineas as $i => [$cuenta, $d, $h]) {
            JournalEntryLine::create([
                'journal_entry_id' => $asiento->id,
                'account_plan_id'  => $cuenta,
                'descripcion'      => $descripcion,
                'debe'             => $d,
                'haber'            => $h,
                'orden'            => $i + 1,
            ]);
        }

        return $asiento;
    }

    /** Un ejercicio cerrado no admite movimientos nuevos. */
    public function exigirEjercicioAbierto(int $empresaId, string $fecha): void
    {
        $momento = Carbon::parse($fecha);

        // En modo cuadre, lo anterior al inicio de gestión se puede corregir.
        if ($this->permiteCuadre($empresaId, $momento)) {
            return;
        }

        $cerrado = EjercicioContable::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('anio', $momento->year)
            ->whereNotNull('cerrado_en')
            ->exists();

        if ($cerrado) {
            throw new \RuntimeException(
                "El ejercicio {$momento->year} está cerrado y ya fue presentado: no admite asientos."
            );
        }

        // El mes también cierra, no solo el año: un período cuyo IVA ya se
        // declaró no puede recibir movimientos nuevos, porque entonces el
        // sistema dejaría de cuadrar con lo que consta en el SRI.
        if ($this->mesCerrado($empresaId, $momento->year, $momento->month)) {
            throw new \RuntimeException(sprintf(
                'El período %02d/%d está cerrado: su IVA ya fue declarado. Para corregirlo hace '
                . 'falta una declaración sustitutiva, no un asiento nuevo.',
                $momento->month, $momento->year,
            ));
        }
    }

    /**
     * ¿Esa fecha cae en lo que el modo «Cuadre de gestión anterior» deja corregir?
     *
     * Solo con el modo encendido y solo antes del inicio de gestión: desde ese mes
     * el ERP responde por lo declarado y un mes cerrado no se toca.
     */
    public function permiteCuadre(int $empresaId, Carbon $momento): bool
    {
        $empresa = \App\Models\Empresa::withoutGlobalScopes()->find($empresaId);

        return $empresa?->modo_cuadre_en !== null
            && $empresa->inicio_gestion_erp !== null
            && $momento->lessThan(Carbon::parse($empresa->inicio_gestion_erp)->startOfMonth());
    }

    /** ¿El mes está cerrado, por inventario o porque ya se declaró su IVA? */
    public function mesCerrado(int $empresaId, int $anio, int $mes): bool
    {
        return PeriodoContable::where('empresa_id', $empresaId)
            ->where('anio', $anio)->where('mes', $mes)
            ->whereNotNull('cerrado_en')
            ->exists();
    }

    /**
     * Cierra el mes al presentar su declaración.
     *
     * Se llama desde el momento en que alguien marca el período como
     * presentado: a partir de ahí lo que consta en el SRI y lo que dice el
     * sistema tienen que seguir siendo lo mismo.
     */
    public function cerrarMes(int $empresaId, int $anio, int $mes, ?int $declaracionId = null, string $motivo = 'iva'): PeriodoContable
    {
        $periodo = PeriodoContable::firstOrNew([
            'empresa_id' => $empresaId, 'anio' => $anio, 'mes' => $mes,
        ]);

        $periodo->fill([
            'cerrado_en'     => $periodo->cerrado_en ?? now(),
            'cerrado_por'    => $periodo->cerrado_por ?? auth()->id(),
            'declaracion_id' => $declaracionId ?? $periodo->declaracion_id,
            'motivo'         => $motivo,
        ])->save();

        return $periodo;
    }

    /**
     * Cierra a mano un mes anterior al inicio de gestión, ya cuadrado.
     *
     * Esos meses no los cierra el 104: su declaración se cargó o se cuadró en modo
     * cuadre. Se exige solo lo que hace falta para cerrar: la declaración del mes
     * registrada y ningún asiento descuadrado. Desde el inicio de gestión el mes
     * se cierra al registrar el 104 en Cumplimiento, con su auditoría completa.
     */
    public function cerrarMesCuadre(int $empresaId, int $anio, int $mes): PeriodoContable
    {
        $empresa = \App\Models\Empresa::withoutGlobalScopes()->findOrFail($empresaId);
        $momento = Carbon::create($anio, $mes, 1);

        if (! $empresa->inicio_gestion_erp || ! $momento->lessThan(Carbon::parse($empresa->inicio_gestion_erp)->startOfMonth())) {
            throw new \RuntimeException('Este mes es de la gestión del ERP: se cierra al registrar su 104 en Cumplimiento.');
        }

        if ($this->mesCerrado($empresaId, $anio, $mes)) {
            throw new \RuntimeException(sprintf('El período %02d/%d ya está cerrado.', $mes, $anio));
        }

        $declaracion = \App\Models\Declaracion::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)->where('tipo', 'f104')
            ->where('anio', $anio)->where('mes', $mes)
            ->latest('id')->first();

        if (! $declaracion) {
            throw new \RuntimeException(sprintf('Falta registrar el 104 de %02d/%d en Cumplimiento: sin lo presentado no hay contra qué cuadrar.', $mes, $anio));
        }

        $descuadrados = JournalEntry::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
            ->where('status', 'confirmado')
            ->where('esta_cuadrado', false)
            ->count();

        if ($descuadrados) {
            throw new \RuntimeException(sprintf('Hay %d asiento(s) descuadrado(s) en %02d/%d: corrígelos antes de cerrar.', $descuadrados, $mes, $anio));
        }

        return $this->cerrarMes($empresaId, $anio, $mes, $declaracion->id, 'cuadre');
    }

    /**
     * Reabre un mes. Solo para corregir un cierre hecho por error: lo que ya se
     * presentó al SRI se corrige con una sustitutiva, no reabriendo.
     */
    public function reabrirMes(int $empresaId, int $anio, int $mes): void
    {
        PeriodoContable::where('empresa_id', $empresaId)
            ->where('anio', $anio)->where('mes', $mes)
            ->update(['cerrado_en' => null, 'cerrado_por' => null, 'declaracion_id' => null]);
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
