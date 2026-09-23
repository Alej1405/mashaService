<?php

namespace App\Services;

use App\Models\AccountPlan;
use App\Models\CatalogoSri;
use App\Models\MapaSri;
use Illuminate\Support\Facades\Cache;

/**
 * La puerta única a los catálogos del SRI.
 *
 * Tres reglas, y de ellas sale todo lo demás:
 *
 * 1. Un código nunca se busca solo. Siempre anexo + tabla + fecha. El "02" de
 *    la tabla 2 del ATS y el "02" de la tabla 1 del REBEFICS no tienen nada
 *    que ver, y sin el anexo se pisan.
 * 2. El ERP no guarda códigos del SRI dentro de sus tablas: guarda lo suyo y
 *    `mapa_sri` traduce. Añadir un anexo nuevo es añadir filas, no columnas.
 * 3. El número sale del asiento, nunca de otra declaración. El 104, el archivo
 *    de la Superintendencia y el balance leen el mismo libro diario; si no
 *    cuadran, es que faltan cuentas por mapear, no que haya dos verdades.
 */
class CatalogoSriService
{
    /** Los anexos que el módulo conoce hoy. Agregar uno es agregar su JSON. */
    public const ANEXOS = [
        'ats'      => 'Anexo transaccional simplificado',
        'rebefics' => 'Registro de beneficiarios finales',
        'adi'      => 'Anexo de dividendos',
        'f104'     => 'Formulario 104 · IVA',
    ];

    /** Los códigos de una tabla, para llenar un desplegable. */
    public function opciones(string $anexo, string $tabla, ?string $fecha = null): array
    {
        $fecha ??= now()->toDateString();

        return Cache::remember(
            "sri:{$anexo}:{$tabla}:{$fecha}",
            now()->addDay(),
            fn () => CatalogoSri::de($anexo, $tabla)->vigente($fecha)
                ->orderBy('codigo')
                ->pluck('descripcion', 'codigo')
                ->all(),
        );
    }

    /** Una fila del catálogo, o null si ese código no rige en esa fecha. */
    public function buscar(string $anexo, string $tabla, string $codigo, ?string $fecha = null): ?CatalogoSri
    {
        return CatalogoSri::de($anexo, $tabla)->vigente($fecha)
            ->where('codigo', $codigo)
            ->orderByDesc('vigente_desde')
            ->first();
    }

    /**
     * Traduce algo del ERP al código que espera un anexo.
     *
     * Lo que la empresa haya ajustado manda sobre la regla del sistema: el ERP
     * es de todos los clientes, pero el anexo lo presenta cada uno.
     */
    public function codigo(string $entidad, string $clave, string $anexo, string $tabla, ?int $empresaId = null): ?string
    {
        return MapaSri::query()
            ->where('entidad', $entidad)->where('clave', $clave)
            ->where('anexo', $anexo)->where('tabla', $tabla)
            ->when($empresaId, fn ($q) => $q->where(fn ($s) => $s->where('empresa_id', $empresaId)->orWhereNull('empresa_id')))
            ->when(! $empresaId, fn ($q) => $q->whereNull('empresa_id'))
            ->orderByRaw('empresa_id is null')   // primero el ajuste de la empresa
            ->value('codigo');
    }

    /**
     * El concepto de retención de renta con el porcentaje que regía ese día.
     *
     * La tabla 3.10 del ATS se reforma varias veces al año: en 2026 cambió en
     * febrero, marzo, agosto y el 6 de agosto. Una retención de julio se
     * declara con el porcentaje de julio, no con el de hoy.
     */
    public function conceptoRenta(string $conceptoDelErp, string $fecha, ?int $empresaId = null): ?array
    {
        $codigo = $this->codigo('retencion_renta', $conceptoDelErp, 'ats', '3.10', $empresaId);

        if (! $codigo) {
            return null;
        }

        $fila = $this->buscar('ats', '3.10', $codigo, $fecha);

        return $fila ? [
            'codigo'      => $fila->codigo,
            'descripcion' => $fila->descripcion,
            'porcentaje'  => $fila->porcentaje,
            'grupo'       => $fila->grupo,
            'rige_desde'  => $fila->vigente_desde?->toDateString(),
        ] : null;
    }

    /**
     * ¿Ese par de códigos puede ir junto en el anexo?
     *
     * Los catálogos no son listas sueltas: el tipo de beneficiario 01 del anexo
     * de dividendos solo admite identificación R, C o P; el comprobante 9 del
     * ATS solo admite el sustento 02. El SRI rechaza el archivo entero por esto.
     */
    public function admite(string $anexo, string $tabla, string $codigo, string $codigoHijo, ?string $fecha = null): bool
    {
        $fila = $this->buscar($anexo, $tabla, $codigo, $fecha);

        if (! $fila) {
            return false;
        }

        $admitidos = $fila->compatibles();

        // Sin restricción publicada, la tabla no limita nada.
        return $admitidos === [] || in_array(ltrim($codigoHijo, '0') ?: '0', array_map(
            fn ($c) => ltrim($c, '0') ?: '0',
            $admitidos,
        ), true);
    }

    /**
     * Coherencia con la Superintendencia.
     *
     * El SRI y la SCVS reciben el mismo ejercicio contado con dos lenguajes: el
     * casillero y el código del catálogo. Este método no compara declaraciones
     * —eso sería comparar dos copias— sino que proyecta el libro diario a los
     * dos lados y avisa dónde se separan.
     *
     * @return array{ingresos: float, ingresos_supercias: float, diferencia_ingresos: float,
     *               gastos: float, gastos_supercias: float, diferencia_gastos: float,
     *               sin_codigo_supercias: int, sin_concepto_sri: int, cuadra: bool}
     */
    public function cuadreConSupercias(int $empresaId, int $anio): array
    {
        $contable = app(ContabilidadService::class)->saldos($empresaId, "{$anio}-12-31", $anio);
        $porCodigo = app(SuperciasService::class)->saldosPorCodigo($empresaId, $anio);

        // El catálogo de la SCVS abre en 4 los ingresos y en 5 los costos y gastos.
        $sumaRaiz = fn (string $raiz) => round(array_sum(array_filter(
            $porCodigo,
            fn ($v, $codigo) => str_starts_with((string) $codigo, $raiz),
            ARRAY_FILTER_USE_BOTH,
        )), 2);

        $ingresosScvs = $sumaRaiz('4');
        $gastosScvs   = $sumaRaiz('5');
        $gastos       = round($contable['costos'] + $contable['gastos'], 2);

        $sinCodigo = app(SuperciasService::class)->cuentasSinCodigo($empresaId);
        $sinConcepto = AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('accepts_movements', true)
            ->whereIn('type', ['gasto', 'costo'])
            ->whereNull('codigo_supercias')
            ->count();

        $difIngresos = round($contable['ingresos'] - $ingresosScvs, 2);
        $difGastos   = round($gastos - $gastosScvs, 2);

        return [
            'ingresos'             => $contable['ingresos'],
            'ingresos_supercias'   => $ingresosScvs,
            'diferencia_ingresos'  => $difIngresos,
            'gastos'               => $gastos,
            'gastos_supercias'     => $gastosScvs,
            'diferencia_gastos'    => $difGastos,
            'sin_codigo_supercias' => $sinCodigo,
            'sin_concepto_sri'     => $sinConcepto,
            'cuadra'               => abs($difIngresos) < 0.01 && abs($difGastos) < 0.01,
        ];
    }

    /** Cuántos códigos hay cargados por anexo: sirve para saber si falta sembrar. */
    public function inventario(): array
    {
        return CatalogoSri::query()
            ->selectRaw('anexo, count(*) as total, count(distinct tabla) as tablas')
            ->groupBy('anexo')
            ->get()
            ->mapWithKeys(fn ($f) => [$f->anexo => [
                'nombre' => self::ANEXOS[$f->anexo] ?? $f->anexo,
                'codigos' => (int) $f->total,
                'tablas'  => (int) $f->tablas,
            ]])
            ->all();
    }
}
