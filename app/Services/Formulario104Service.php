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
        $this->arrastres($empresaId, $anio, $mes, $c, $avisos);
        $this->liquidar($c);

        $empresa = DB::table('empresas')->where('id', $empresaId)->first();

        return [
            'casilleros' => array_map(fn ($v) => round((float) $v, 2), $c),
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

        if ($t['compras_numero'] === 0 && $t['ventas_numero'] === 0) {
            return;
        }

        $c['500'] += $t['compras_gravadas'];
        $c['510'] += $t['compras_gravadas'];
        $c['520'] += $t['compras_iva'];
        $c['507'] += $t['compras_cero'];
        $c['517'] += $t['compras_cero'];
        $c['115'] += $t['compras_numero'];

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

        $avisos[] = "Se sumaron {$detalle} importadas del portal del SRI que no están registradas "
            . 'en el ERP. Contablemente no existen: el asiento hay que hacerlo aparte.';

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

    /** La liquidación: proporcionalidad, impuesto causado y lo que se paga. */
    private function liquidar(array &$c): void
    {
        $c['482'] = $c['429'];
        $c['484'] = $c['482'] - $c['485'];
        $c['499'] = $c['483'] + $c['484'];

        /*
         * Factor de proporcionalidad.
         *
         * Existe para las empresas que venden unas cosas gravadas y otras no:
         * solo esa proporción del IVA de compras es crédito tributario.
         *
         * Un mes sin ventas NO es ese caso. El crédito no se pierde: se acumula
         * y viaja al mes siguiente. Poner el factor en cero cuando no hay
         * ventas manda todo el IVA al gasto y le cuesta dinero real a la
         * empresa cada mes que pasa.
         *
         * Marco: skill `contabilidad-ec`, crédito tributario del IVA.
         */
        $conDerecho = $c['411'] + $c['412'] + $c['420'] + $c['435'] + $c['415'] + $c['416'] + $c['417'] + $c['418'];
        $c['563'] = $c['419'] > 0
            ? round($conDerecho / $c['419'], 4)
            : 1.0;   // sin ventas, el crédito es íntegro

        $ivaCompras = $c['520'] + $c['521'] + $c['534'] + $c['560'] + $c['523'] + $c['524'] + $c['525'] + $c['526'] - $c['527'];
        $c['564'] = round($ivaCompras * $c['563'], 2);
        $c['565'] = round($ivaCompras - $c['564'], 2);

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

    /** Los casilleros que existen, según el catálogo oficial. */
    private function codigosDelFormulario(): array
    {
        return \App\Models\CatalogoSri::where('anexo', 'f104')->pluck('codigo')->all();
    }
}
