<?php

namespace App\Services;

use App\Models\Declaracion;
use App\Models\Gasto;
use App\Models\Purchase;
use App\Models\Retencion;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Formulario 104 · IVA mensual.
 *
 * Arma los casilleros desde las ventas, las compras del inventario, los gastos
 * del módulo de contabilidad y las retenciones del mes. No inventa nada: si el
 * mes anterior no está declarado en el sistema, avisa en lugar de arrastrar
 * cero, porque un saldo de crédito tributario perdido no se recupera.
 *
 * Marco: skill `declaraciones-sri-ec`, referencia del formulario 104.
 */
class Formulario104Service
{
    /** Casilleros que se traen de la declaración del mes anterior. */
    private const ARRASTRES = ['483' => '485', '605' => '615', '606' => '617', '607' => '618', '608' => '619'];

    public function __construct(
        private readonly CatalogoSriService $catalogo,
        private readonly ContabilidadService $contabilidad,
        private readonly ImportadorComprobantesSri $importados,
    ) {
    }

    /**
     * @return array{casilleros: array<string, float>, avisos: array<int, string>,
     *               periodo: string, ruc: string|null}
     */
    public function calcular(int $empresaId, int $anio, int $mes): array
    {
        $desde = Carbon::create($anio, $mes, 1)->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();
        $avisos = [];

        $c = array_fill_keys($this->codigosDelFormulario(), 0.0);

        $this->ventas($empresaId, $desde, $hasta, $c);
        $this->compras($empresaId, $desde, $hasta, $c, $avisos);
        $this->delPortal($empresaId, $anio, $mes, $c, $avisos);
        $this->retencionesEfectuadas($empresaId, $desde, $hasta, $c);
        $this->ivaComoElPortal($empresaId, $hasta, $c, $avisos);
        $this->arrastres($empresaId, $anio, $mes, $c, $avisos);
        $this->liquidar($c);

        $empresa = DB::table('empresas')->where('id', $empresaId)->first();

        // El factor de proporcionalidad lleva cuatro decimales; el resto, dos.
        $casilleros = array_map(fn ($v) => round((float) $v, 2), $c);
        $casilleros['563'] = round((float) $c['563'], 4);

        return [
            'casilleros' => $casilleros,
            'avisos'     => $avisos,
            'periodo'    => sprintf('%02d/%d', $mes, $anio),
            'ruc'        => $empresa->ruc ?? null,
            'razon'      => $empresa->name ?? null,
        ];
    }

    /** Ventas del mes, separadas por tarifa. */
    private function ventas(int $empresaId, Carbon $desde, Carbon $hasta, array &$c): void
    {
        $lineas = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.empresa_id', $empresaId)
            ->where('sales.estado', 'confirmado')
            ->whereBetween('sales.fecha', [$desde, $hasta])
            ->selectRaw('sale_items.aplica_iva, sum(sale_items.subtotal) as base, sum(sale_items.iva_monto) as iva')
            ->groupBy('sale_items.aplica_iva')
            ->get();

        foreach ($lineas as $l) {
            if ($l->aplica_iva) {
                $c['401'] += (float) $l->base;   // bruto
                $c['411'] += (float) $l->base;   // neto: sin notas de crédito todavía
                $c['421'] += (float) $l->iva;
            } else {
                // Tarifa 0 % con derecho a crédito: la actividad de la empresa
                // decide si va al 405 o al 403. Por defecto, con derecho.
                $c['405'] += (float) $l->base;
                $c['415'] += (float) $l->base;
            }
        }

        $ventas = Sale::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('estado', 'confirmado')
            ->whereBetween('fecha', [$desde, $hasta])
            ->get(['tipo_venta', 'subtotal', 'iva']);

        $c['111'] = $ventas->count();
        // Contado y crédito: lo que decide cuándo se liquida el impuesto.
        $c['480'] = (float) $ventas->where('tipo_venta', '!=', 'credito')->sum('subtotal');
        $c['481'] = (float) $ventas->where('tipo_venta', '=', 'credito')->sum('subtotal');

        $c['409'] = $c['401'] + $c['402'] + $c['410'] + $c['425'] + $c['403'] + $c['404']
            + $c['405'] + $c['406'] + $c['407'] + $c['408'];
        $c['419'] = $c['411'] + $c['412'] + $c['420'] + $c['435'] + $c['413'] + $c['414']
            + $c['415'] + $c['416'] + $c['417'] + $c['418'];
        $c['429'] = $c['421'] + $c['422'] + $c['430'] + $c['445'] + $c['423'] - $c['424'];
    }

    /**
     * Compras del inventario y gastos de contabilidad: las dos entran al mismo
     * casillero. Un gasto de alimentación y una compra de materia prima son, a
     * ojos del 104, la misma adquisición con derecho a crédito tributario.
     */
    private function compras(int $empresaId, Carbon $desde, Carbon $hasta, array &$c, array &$avisos): void
    {
        $compras = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.empresa_id', $empresaId)
            ->where('purchases.status', 'confirmado')
            ->whereBetween('purchases.date', [$desde, $hasta])
            ->selectRaw('purchase_items.aplica_iva, sum(purchase_items.subtotal) as base, sum(purchase_items.iva_monto) as iva')
            ->groupBy('purchase_items.aplica_iva')
            ->get();

        foreach ($compras as $l) {
            if ($l->aplica_iva) {
                $c['500'] += (float) $l->base;
                $c['510'] += (float) $l->base;
                $c['520'] += (float) $l->iva;
            } else {
                $c['507'] += (float) $l->base;
                $c['517'] += (float) $l->base;
            }
        }

        // Los gastos del módulo de contabilidad, línea por línea.
        $gastos = DB::table('gasto_lineas')
            ->join('gastos', 'gastos.id', '=', 'gasto_lineas.gasto_id')
            ->where('gastos.empresa_id', $empresaId)
            ->where('gastos.estado', 'confirmado')
            ->whereBetween('gastos.fecha', [$desde, $hasta])
            ->selectRaw('gasto_lineas.porcentaje_iva, sum(gasto_lineas.base) as base, sum(gasto_lineas.iva) as iva')
            ->groupBy('gasto_lineas.porcentaje_iva')
            ->get();

        foreach ($gastos as $g) {
            $tarifa = (float) $g->porcentaje_iva;

            match (true) {
                $tarifa >= 15.0 => [$c['500'] += (float) $g->base, $c['510'] += (float) $g->base, $c['520'] += (float) $g->iva],
                $tarifa == 5.0  => [$c['540'] += (float) $g->base, $c['550'] += (float) $g->base, $c['560'] += (float) $g->iva],
                $tarifa > 0     => [$c['530'] += (float) $g->base, $c['533'] += (float) $g->base, $c['534'] += (float) $g->iva],
                default         => [$c['507'] += (float) $g->base, $c['517'] += (float) $g->base],
            };
        }

        $c['115'] = Purchase::withoutGlobalScopes()->where('empresa_id', $empresaId)
                ->whereBetween('date', [$desde, $hasta])->count()
            + Gasto::withoutGlobalScopes()->where('empresa_id', $empresaId)
                ->where('estado', 'confirmado')->whereBetween('fecha', [$desde, $hasta])->count();

        $c['509'] = $c['500'] + $c['501'] + $c['530'] + $c['540'] + $c['502'] + $c['503']
            + $c['504'] + $c['505'] + $c['506'] + $c['507'] + $c['508'];
        $c['519'] = $c['510'] + $c['511'] + $c['533'] + $c['550'] + $c['512'] + $c['513']
            + $c['514'] + $c['515'] + $c['516'] + $c['517'] + $c['518'];
        $c['529'] = $c['520'] + $c['521'] + $c['534'] + $c['560'] + $c['522'] + $c['523']
            + $c['524'] + $c['525'] + $c['526'] - $c['527'];

        // El mapeo es automático, así que sin línea no queda ninguna; lo que
        // puede quedar es alguna cuya línea el sistema no tuvo clara.
        $porConfirmar = app(MapeoSuperciasService::class)->porRevisar($empresaId);

        if ($porConfirmar > 0) {
            $avisos[] = "{$porConfirmar} cuentas tienen su línea del estado puesta por el sistema y "
                . 'sin confirmar. No afecta a este formulario, pero sí al balance de la Superintendencia.';
        }
    }

    /**
     * Lo que se importó del portal del SRI y no casa con ningún documento
     * del ERP.
     *
     * Es el caso de la empresa que empieza a mitad de año: el movimiento de
     * enero a julio no está registrado, pero el portal lo tiene. Solo entra lo
     * que quedó sin conciliar, porque lo demás ya se contó arriba.
     */
    private function delPortal(int $empresaId, int $anio, int $mes, array &$c, array &$avisos): void
    {
        $t = $this->importados->totalesDelPeriodo($empresaId, $anio, $mes);

        if ($t['compras_numero'] === 0 && $t['ventas_numero'] === 0 && $t['personales_numero'] === 0) {
            return;
        }

        // Las personales suman como compras: el SRI cuenta su IVA aunque no haya gasto.
        $c['500'] += $t['compras_gravadas'] + $t['personales_gravadas'];
        $c['510'] += $t['compras_gravadas'] + $t['personales_gravadas'];
        $c['520'] += $t['compras_iva'] + $t['personales_iva'];
        $c['507'] += $t['compras_cero'] + $t['personales_cero'];
        $c['517'] += $t['compras_cero'] + $t['personales_cero'];
        $c['115'] += $t['compras_numero'] + $t['personales_numero'];

        $c['401'] += $t['ventas_gravadas'];
        $c['411'] += $t['ventas_gravadas'];
        $c['421'] += $t['ventas_iva'];
        $c['405'] += $t['ventas_cero'];
        $c['415'] += $t['ventas_cero'];
        $c['111'] += $t['ventas_numero'];

        // Los totales se rehacen: las sumas de arriba corrieron antes de esto.
        $c['409'] = $c['401'] + $c['402'] + $c['410'] + $c['425'] + $c['403'] + $c['404']
            + $c['405'] + $c['406'] + $c['407'] + $c['408'];
        $c['419'] = $c['411'] + $c['412'] + $c['420'] + $c['435'] + $c['413'] + $c['414']
            + $c['415'] + $c['416'] + $c['417'] + $c['418'];
        $c['429'] = $c['421'] + $c['422'] + $c['430'] + $c['445'] + $c['423'] - $c['424'];
        $c['509'] = $c['500'] + $c['501'] + $c['530'] + $c['540'] + $c['502'] + $c['503']
            + $c['504'] + $c['505'] + $c['506'] + $c['507'] + $c['508'];
        $c['519'] = $c['510'] + $c['511'] + $c['533'] + $c['550'] + $c['512'] + $c['513']
            + $c['514'] + $c['515'] + $c['516'] + $c['517'] + $c['518'];
        $c['529'] = $c['520'] + $c['521'] + $c['534'] + $c['560'] + $c['522'] + $c['523']
            + $c['524'] + $c['525'] + $c['526'] - $c['527'];

        $detalle = trim(($t['compras_numero'] ? "{$t['compras_numero']} compras " : '')
            . ($t['ventas_numero'] ? "{$t['ventas_numero']} ventas" : ''));

        if ($detalle !== '') {
            $avisos[] = "Se sumaron {$detalle} importadas del portal del SRI que no están registradas "
                . 'en el ERP. Contablemente no existen: el asiento hay que hacerlo aparte.';
        }

        if ($t['personales_numero'] > 0) {
            $avisos[] = "Se sumaron {$t['personales_numero']} compras personales hechas con el RUC: "
                . 'solo su IVA, como lo cuenta el SRI. No son gasto ni tienen asiento.';
        }

        if ($t['estimados'] > 0) {
            $avisos[] = "{$t['estimados']} ventas importadas traen la base despejada del total, porque "
                . 'el archivo del SRI no publica el desglose. Revísalas antes de presentar.';
        }
    }

    /** Lo retenido a los proveedores, por porcentaje, a su casillero del 104. */
    private function retencionesEfectuadas(int $empresaId, Carbon $desde, Carbon $hasta, array &$c): void
    {
        $lineas = DB::table('retencion_lineas')
            ->join('retenciones', 'retenciones.id', '=', 'retencion_lineas.retencion_id')
            ->where('retenciones.empresa_id', $empresaId)
            ->whereBetween('retenciones.fecha', [$desde, $hasta])
            ->where('retencion_lineas.tipo', 'iva')
            ->selectRaw('retencion_lineas.porcentaje, sum(retencion_lineas.valor) as valor')
            ->groupBy('retencion_lineas.porcentaje')
            ->get();

        foreach ($lineas as $l) {
            $casillero = $this->catalogo->codigo(
                'retencion_iva', (string) (int) $l->porcentaje, 'f104', 'retencion', $empresaId,
            );

            if ($casillero) {
                $c[$casillero] += (float) $l->valor;
            }
        }

        $c['799'] = $c['721'] + $c['723'] + $c['725'] + $c['727'] + $c['729'] + $c['731'];
        $c['801'] = $c['799'] - $c['800'] - $c['802'];

        // El 119 cuenta las liquidaciones de compra emitidas.
        $c['119'] = Retencion::withoutGlobalScopes()->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$desde, $hasta])->count();
    }

    /**
     * Los saldos que vienen del mes anterior.
     *
     * Sin la declaración previa en el sistema no se pone cero: se avisa. Un cero
     * inventado pierde crédito tributario que la empresa ya pagó.
     */
    private function arrastres(int $empresaId, int $anio, int $mes, array &$c, array &$avisos): void
    {
        $previo = Carbon::create($anio, $mes, 1)->subMonth();

        $anterior = Declaracion::where('empresa_id', $empresaId)
            ->where('tipo', 'f104')->where('anio', $previo->year)->where('mes', $previo->month)
            ->listas()->latest('generado_en')->first();

        if (! $anterior) {
            $empresa = \App\Models\Empresa::withoutGlobalScopes()->find($empresaId);
            $inicio = $empresa ? app(GestionSriService::class)->inicioDeGestion($empresa) : null;

            // Antes del inicio de gestión no hay nada que arrastrar: el ERP no
            // responde por esos meses y avisar de ellos es ruido, no control.
            if ($inicio && $previo->lessThan($inicio)) {
                return;
            }

            $avisos[] = 'No hay 104 de ' . $previo->format('m/Y') . ' en el sistema: '
                . 'los casilleros de arrastre (483, 605, 606, 607, 608) quedaron en cero. '
                . 'Si ese mes tenía crédito tributario, cárgalo a mano antes de declarar.';

            return;
        }

        foreach (self::ARRASTRES as $destino => $origen) {
            $c[$destino] = $anterior->casillero($origen);
        }
    }

    /**
     * El IVA de cada línea como lo calcula el formulario: base neta por la tarifa.
     *
     * Sumar el IVA de cada factura da centavos distintos (septiembre 2026 de Rivet:
     * 58,87 sumando facturas, 58,89 en el portal) y el comprobante del SRI manda.
     * Solo la tarifa general; la de 5 % y la variable turística siguen sumadas.
     */
    private function ivaComoElPortal(int $empresaId, Carbon $hasta, array &$c, array &$avisos): void
    {
        $tarifa = \App\Models\TarifaIva::generalEn($hasta, $empresaId)?->porcentaje;

        if ($tarifa === null) {
            $avisos[] = 'No hay tarifa general de IVA vigente a ' . $hasta->format('d/m/Y')
                . ': el IVA se tomó sumando las facturas y puede diferir en centavos del portal.';

            return;
        }

        $t = (float) $tarifa / 100;

        foreach (['421' => '411', '422' => '412', '520' => '510', '521' => '511', '522' => '512'] as $iva => $base) {
            $c[$iva] = round((float) $c[$base] * $t, 2);
        }

        $c['429'] = $c['421'] + $c['422'] + $c['430'] + $c['445'] + $c['423'] - $c['424'];
        $c['529'] = $c['520'] + $c['521'] + $c['534'] + $c['560'] + $c['522'] + $c['523']
            + $c['524'] + $c['525'] + $c['526'] - $c['527'];
    }

    /** La liquidación: proporcionalidad, impuesto causado y lo que se paga. */
    private function liquidar(array &$c): void
    {
        $c['482'] = $c['429'];
        $c['484'] = $c['482'] - $c['485'];
        $c['499'] = $c['483'] + $c['484'];

        /*
         * Factor de proporcionalidad, como lo calcula el formulario del SRI:
         * (411+412+420+435+415+416+417+418) / 419.
         *
         * Sin ventas (419 = 0) el portal pone el factor en 0,0000: el IVA de las
         * compras de ese mes no entra al crédito (564 = 0), la 565 queda en 0 y
         * el saldo para el próximo mes es el que se traía. Así lo dice el
         * comprobante de septiembre 2026 de Rivet (serial 873119599526). Antes el
         * ERP ponía el factor en 1 y sumaba crédito que el SRI no reconoce.
         *
         * Marco: skill `declaraciones-sri-ec`, el comprobante del SRI manda.
         */
        $conDerecho = $c['411'] + $c['412'] + $c['420'] + $c['435'] + $c['415'] + $c['416'] + $c['417'] + $c['418'];
        $c['563'] = $c['419'] > 0 ? round($conDerecho / $c['419'], 4) : 0.0;

        $ivaCompras = $c['520'] + $c['521'] + $c['534'] + $c['560'] + $c['523'] + $c['524'] + $c['525'] + $c['526'] - $c['527'];
        $c['564'] = round($ivaCompras * $c['563'], 2);
        $c['565'] = $c['419'] > 0 ? round($ivaCompras - $c['564'], 2) : 0.0;

        $diferencia = round($c['499'] - $c['564'], 2);
        $c['601'] = max($diferencia, 0);
        $c['602'] = max(-$diferencia, 0);

        $subtotal = $c['601'] - $c['602'] - $c['603'] - $c['604'] - $c['605'] - $c['606'] - $c['607']
            - $c['608'] - $c['609'] - $c['622'] - $c['623']
            + $c['610'] + $c['611'] + $c['612'] + $c['613'] + $c['614'];

        $c['620'] = max(round($subtotal, 2), 0);
        $c['699'] = $c['620'] + $c['621'];

        // Lo que no se pudo usar este mes viaja al siguiente.
        $c['615'] = max(round(-$subtotal, 2), 0);
        $c['617'] = max(round($c['606'] + $c['609'] - max($subtotal, 0), 2), 0);

        $c['859'] = $c['699'] + $c['801'];
        $c['902'] = $c['859'] - $c['898'];
        $c['999'] = $c['902'] + $c['903'] + $c['904'];
    }

    /**
     * Totales y fórmulas que el portal calcula solo: no se escriben, se comparan.
     * Son los de las fórmulas de liquidar(), los subtotales de cada bloque y el IVA
     * de cada línea, que el formulario saca de la base neta por la tarifa (la 520
     * sale de la 510; la 421, de la 411).
     */
    public const CALCULADOS = [
        '409', '419', '429', '482', '484', '499', '509', '519', '529', '563', '564', '565',
        '601', '602', '615', '617', '620', '699', '799', '801', '859', '902', '999',
        // IVA de ventas y de compras por tarifa
        '421', '422', '430', '445', '520', '521', '522', '534', '560',
    ];

    /**
     * Qué es cada casilla en palabras de quien no es contador. El nombre oficial
     * se muestra igual, más pequeño: es el que aparece en el portal.
     */
    public const EN_PALABRAS = [
        '111' => 'Cuántas facturas de venta emitiste en el mes',
        '113' => 'Cuántas facturas de venta anulaste',
        '115' => 'Cuántas facturas de compra recibiste en el mes',
        '117' => 'Cuántas notas de venta recibiste',
        '119' => 'Cuántas liquidaciones de compra emitiste',
        '401' => 'Ventas con IVA 15 %, sin sumar el IVA',
        '411' => 'Lo mismo que la 401, restando notas de crédito',
        '405' => 'Ventas con IVA 0 %',
        '415' => 'Lo mismo que la 405, restando notas de crédito',
        '480' => 'De las ventas con IVA, lo que cobraste al contado',
        '481' => 'De las ventas con IVA, lo que quedó a crédito',
        '483' => 'Impuesto que quedó por liquidar del mes anterior',
        '500' => 'Compras con IVA 15 %, sin sumar el IVA',
        '501' => 'Activos fijos comprados con IVA 15 %, sin sumar el IVA',
        '502' => 'Compras con IVA que no te dan crédito tributario, sin sumar el IVA',
        '507' => 'Compras con IVA 0 %',
        '510' => 'Lo mismo que la 500, restando notas de crédito',
        '512' => 'Lo mismo que la 502, restando notas de crédito',
        '517' => 'Lo mismo que la 507, restando notas de crédito',
        '540' => 'Compras con IVA 5 %, sin sumar el IVA',
        '550' => 'Lo mismo que la 540, restando notas de crédito',
        '605' => 'Crédito tributario que te sobró el mes pasado',
        '606' => 'Crédito por retenciones que te sobró el mes pasado',
        '609' => 'IVA que tus clientes te retuvieron este mes',
        '721' => 'IVA que retuviste a tus proveedores al 10 %',
        '723' => 'IVA que retuviste a tus proveedores al 20 %',
        '725' => 'IVA que retuviste a tus proveedores al 30 %',
        '727' => 'IVA que retuviste a tus proveedores al 50 %',
        '729' => 'IVA que retuviste a tus proveedores al 70 %',
        '731' => 'IVA que retuviste a tus proveedores al 100 %',
    ];

    /** Casilleros que cuentan comprobantes: van sin decimales. */
    public const CONTEOS = ['111', '113', '115', '117', '119'];

    /**
     * Lo que hay que escribir en el portal: solo las casillas con valor que no
     * calcula el propio formulario, agrupadas por la sección donde están.
     *
     * @param  array<string, float|int>  $casilleros
     * @return array<string, array<int, array{codigo: string, nombre: string, valor: float, conteo: bool}>>
     */
    public function paraElPortal(array $casilleros): array
    {
        $catalogo = \App\Models\CatalogoSri::where('anexo', 'f104')->get(['codigo', 'descripcion', 'nombre_tabla'])
            ->keyBy('codigo');

        return collect($casilleros)
            ->filter(fn ($v, $codigo) => abs((float) $v) > 0.004 && ! in_array((string) $codigo, self::CALCULADOS, true))
            ->sortKeys(SORT_NUMERIC)
            ->map(fn ($v, $codigo) => [
                'codigo'  => (string) $codigo,
                'nombre'  => $catalogo[$codigo]->descripcion ?? "Casillero {$codigo}",
                'ayuda'   => self::EN_PALABRAS[(string) $codigo] ?? null,
                'seccion' => $catalogo[$codigo]->nombre_tabla ?? 'Otros',
                'valor'   => (float) $v,
                'conteo'  => in_array((string) $codigo, self::CONTEOS, true),
            ])
            ->groupBy('seccion')
            ->map(fn ($filas) => $filas->map(fn ($f) => collect($f)->except('seccion')->all())->values()->all())
            ->all();
    }

    /** Los casilleros que existen, según el catálogo oficial. */
    private function codigosDelFormulario(): array
    {
        return \App\Models\CatalogoSri::where('anexo', 'f104')->pluck('codigo')->all();
    }
}
