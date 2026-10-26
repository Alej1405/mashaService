<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ATS · Anexo transaccional simplificado.
 *
 * Es el detalle de lo que el 104 resume: un registro por comprobante, con el
 * proveedor, su tipo de identificación, el sustento tributario y la forma de
 * pago, cada uno con el código de su tabla. El SRI cruza los dos, así que los
 * totales de aquí tienen que dar lo mismo que los casilleros de allá.
 *
 * Este servicio arma la estructura; el XML lo escribe el microservicio.
 *
 * Marco: skill `declaraciones-sri-ec`, referencia del ATS.
 */
class AtsService
{
    /** La identificación que el SRI reserva para las ventas sin cliente. */
    public const CONSUMIDOR_FINAL = '9999999999999';

    public function __construct(private readonly CatalogoSriService $catalogo)
    {
    }

    /**
     * @return array{cabecera: array, compras: array, ventas: array,
     *               ventasEstablecimiento: array, anulados: array, avisos: array}
     */
    /**
     * Facturas del mes que todavía no están registradas: las del portal sin clasificar
     * (con o sin detalle traído del SRI) y las compras electrónicas que esperan en
     * borrador. Con una sola, el ATS no se genera: saldría incompleto y no cuadraría
     * con el 104 (agosto 2026 de Rivet se presentó sin 29 facturas por no bloquearlo).
     */
    public function pendientes(int $empresaId, int $anio, int $mes): int
    {
        $desde = \Illuminate\Support\Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
        $hasta = \Illuminate\Support\Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        return DB::table('comprobantes_sri')->where('empresa_id', $empresaId)
                ->whereNull('conciliado_con')->whereNull('destino')
                ->whereBetween('fecha_emision', [$desde, $hasta])->count()
            + DB::table('purchases')->where('empresa_id', $empresaId)
                ->where('status', 'borrador')->where('origen', '!=', 'manual')
                ->whereBetween('date', [$desde, $hasta])->count();
    }

    public function datos(int $empresaId, int $anio, int $mes): array
    {
        $desde = Carbon::create($anio, $mes, 1)->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();
        $avisos = [];

        $empresa = DB::table('empresas')->where('id', $empresaId)->first();

        $compras = $this->compras($empresaId, $desde, $hasta, $avisos);
        $ventas  = $this->ventas($empresaId, $desde, $hasta, $avisos);

        return [
            'cabecera' => [
                // En `empresas` el RUC vive en numero_identificacion: no hay columna `ruc`.
                'IdInformante'    => $this->soloDigitos($empresa->numero_identificacion ?? ''),
                'razonSocial'     => $this->razonSocialDelSri($empresaId) ?? $empresa->name ?? '',
                'anio'            => (string) $anio,
                'mes'             => sprintf('%02d', $mes),
                'numEstabRuc'     => '001',
                'totalVentas'     => number_format(array_sum(array_column($ventas, 'baseImpGrav'))
                    + array_sum(array_column($ventas, 'baseImponible')), 2, '.', ''),
                'codigoOperativo' => 'IVA',
            ],
            'compras'  => $compras,
            'ventas'   => $ventas,
            'ventasEstablecimiento' => [[
                'codEstab'    => '001',
                'ventasEstab' => number_format(array_sum(array_column($ventas, 'baseImpGrav')), 2, '.', ''),
                'ivaComp'     => '0.00',
            ]],
            'anulados' => [],
            'avisos'   => $avisos,
        ];
    }

    /**
     * Las compras: las del inventario y los gastos, en un solo listado, porque
     * el SRI no distingue de qué módulo del ERP salió el documento.
     */
    private function compras(int $empresaId, Carbon $desde, Carbon $hasta, array &$avisos): array
    {
        $registros = [];

        // La base se separa por tarifa leyendo las líneas, no la cabecera: una
        // factura puede traer productos al 15 % y al 0 % a la vez.
        $documentos = DB::table('purchases as p')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.empresa_id', $empresaId)
            ->where('p.status', 'confirmado')
            ->whereBetween('p.date', [$desde, $hasta])
            ->select('p.id', 'p.numero_factura', 'p.date as fecha', 'p.forma_pago', 'p.clave_acceso as autorizacion',
                's.tipo_identificacion', 's.numero_identificacion', 's.nombre', 's.tipo_persona')
            ->selectRaw("'compra' as origen, 'factura' as tipo_documento, p.subtotal, p.iva, p.total, 0 as no_deducible")
            ->selectRaw('(select coalesce(sum(i.subtotal), 0) from purchase_items i where i.purchase_id = p.id and i.aplica_iva) as base_grav')
            ->selectRaw('(select coalesce(sum(i.subtotal), 0) from purchase_items i where i.purchase_id = p.id and not i.aplica_iva) as base_cero')
            ->selectRaw("exists(select 1 from purchase_items i join inventory_items ii on ii.id = i.inventory_item_id
                         where i.purchase_id = p.id and ii.type = 'activo_fijo') as es_activo")
            // Una compra sin ninguna línea al inventario (un servicio por factura electrónica) no es inventario.
            ->selectRaw("exists(select 1 from purchase_items i join inventory_items ii on ii.id = i.inventory_item_id
                         where i.purchase_id = p.id and ii.type <> 'activo_fijo') as es_inventario")
            ->get();

        $gastos = DB::table('gastos as g')
            ->leftJoin('suppliers as s', 's.id', '=', 'g.supplier_id')
            ->where('g.empresa_id', $empresaId)
            ->where('g.estado', 'confirmado')
            ->whereBetween('g.fecha', [$desde, $hasta])
            ->select('g.id', 'g.numero_documento as numero_factura', 'g.fecha', 'g.forma_pago', 'g.autorizacion',
                's.tipo_identificacion', 's.numero_identificacion', 's.nombre', 's.tipo_persona')
            ->selectRaw("'gasto' as origen, g.tipo_documento, g.subtotal, g.iva, g.total, g.no_deducible")
            ->selectRaw('(select coalesce(sum(l.base), 0) from gasto_lineas l where l.gasto_id = g.id and l.iva > 0) as base_grav')
            ->selectRaw('(select coalesce(sum(l.base), 0) from gasto_lineas l where l.gasto_id = g.id and l.iva = 0) as base_cero')
            ->selectRaw('false as es_activo')
            ->selectRaw('false as es_inventario')
            ->get();

        // Las compras personales con el RUC de la empresa no son gasto ni tienen asiento,
        // pero su IVA está en el 104: el SRI cruza los dos, así que van aquí también.
        $personales = DB::table('comprobantes_sri as c')
            ->where('c.empresa_id', $empresaId)
            ->where('c.origen', 'recibido')->where('c.conciliado_con', 'personal')
            ->whereBetween('c.fecha_emision', [$desde, $hasta])
            ->select('c.id', 'c.numero as numero_factura', 'c.fecha_emision as fecha', 'c.clave_acceso as autorizacion',
                'c.identificacion as numero_identificacion', 'c.razon_social as nombre')
            ->selectRaw("null as forma_pago, null as tipo_persona, 'personal' as origen")
            ->selectRaw("case length(c.identificacion) when 13 then 'ruc' when 10 then 'cedula' else 'pasaporte' end as tipo_identificacion")
            ->selectRaw("case c.tipo_comprobante when '03' then 'liquidacion_compra' when '04' then 'nota_credito'
                         when '05' then 'nota_debito' else 'factura' end as tipo_documento")
            ->selectRaw('(c.base_gravada + c.base_cero) as subtotal, c.iva, c.total, 0 as no_deducible, c.base_gravada as base_grav, c.base_cero')
            ->selectRaw('false as es_activo')
            ->selectRaw('false as es_inventario')
            ->get();

        // Lo que llegó del portal y nadie registró no existe en la contabilidad.
        // pendientes() bloquea la generación antes de llegar aquí; esto queda por si acaso.
        $sinRegistrar = $this->pendientes($empresaId, $desde->year, $desde->month);
        if ($sinRegistrar > 0) {
            $avisos[] = "{$sinRegistrar} facturas del portal de este mes no están registradas: no entran al ATS "
                . 'hasta clasificarlas en «Clasificar comprobantes», y el ATS no cuadraría con el 104.';
        }

        foreach ($documentos->concat($gastos)->concat($personales) as $d) {
            if (! $d->numero_identificacion) {
                $avisos[] = "El documento {$d->numero_factura} no tiene proveedor con identificación: "
                    . 'el SRI rechaza el registro sin ella.';

                continue;
            }

            $tipoId = $this->catalogo->codigo(
                'tipo_identificacion', 'compra:' . ($d->tipo_identificacion ?? 'ruc'), 'ats', '2', $empresaId,
            );
            $tipoComprobante = $this->catalogo->codigo(
                'tipo_documento', $d->tipo_documento ?? 'factura', 'ats', '4', $empresaId,
            ) ?? '1';
            $formaPago = $this->catalogo->codigo(
                'forma_pago', $d->forma_pago ?? 'efectivo', 'ats', '13', $empresaId,
            ) ?? '01';

            // El sustento depende de a qué va, no del documento (tabla 5 del ATS):
            //   06 inventario con crédito de IVA · 03 activo fijo con crédito de IVA
            //   01 crédito tributario (bienes y servicios) · 02 costo o gasto sin crédito
            $sustento = match (true) {
                $d->origen === 'compra' && (bool) $d->es_activo     => '03',
                $d->origen === 'compra' && (bool) $d->es_inventario => '06',
                (float) $d->iva <= 0 || (float) $d->no_deducible > 0 => '02',
                default                                          => '01',
            };

            $autorizacion = $this->soloDigitos((string) ($d->autorizacion ?? ''));
            if ($autorizacion === '') {
                $avisos[] = "El documento {$d->numero_factura} no tiene número de autorización: el SRI lo rechaza. "
                    . 'Regístralo desde su clave de acceso.';
            }

            if (! $this->catalogo->admite('ats', '4', $tipoComprobante, $sustento, $d->fecha)) {
                $avisos[] = "El comprobante {$d->numero_factura} (tipo {$tipoComprobante}) "
                    . "no admite el sustento {$sustento}: el SRI rechazaría el anexo completo.";
            }

            $numero = $this->partirNumero($d->numero_factura);

            $registros[] = [
                'codSustento'   => $sustento,
                'tpIdProv'      => $tipoId ?? '01',
                'idProv'        => $this->soloDigitos($d->numero_identificacion),
                'tipoComprobante' => str_pad($tipoComprobante, 2, '0', STR_PAD_LEFT),
                'parteRel'      => 'NO',
                'fechaRegistro' => Carbon::parse($d->fecha)->format('d/m/Y'),
                'establecimiento' => $numero['estab'],
                'puntoEmision'  => $numero['punto'],
                'secuencial'    => $numero['secuencial'],
                'fechaEmision'  => Carbon::parse($d->fecha)->format('d/m/Y'),
                'autorizacion'  => $autorizacion,
                'baseNoGraIva'  => '0.00',
                // Si las líneas no suman nada (documento viejo), se reparte por la cabecera.
                'baseImponible' => $this->monto(((float) $d->base_grav + (float) $d->base_cero) > 0
                    ? $d->base_cero : ((float) $d->iva > 0 ? 0 : $d->subtotal)),
                'baseImpGrav'   => $this->monto(((float) $d->base_grav + (float) $d->base_cero) > 0
                    ? $d->base_grav : ((float) $d->iva > 0 ? $d->subtotal : 0)),
                'montoIce'      => '0.00',
                'montoIva'      => $this->monto($d->iva),
                'valRetBien10'  => '0.00',
                'valRetServ20'  => '0.00',
                'valorRetBienes' => '0.00',
                'valRetServ50'  => '0.00',
                'valorRetServicios' => '0.00',
                'valRetServ100' => '0.00',
                'totbasesImpReemb' => '0.00',
                'pagoLocExt'    => '01',
                'formaPago'     => [$formaPago],
                'air'           => $this->retencionesDe($empresaId, $d->origen, $d->id),
            ];
        }

        return $registros;
    }

    /** Las retenciones de renta del documento, con su código de la tabla 3.10. */
    private function retencionesDe(int $empresaId, string $origen, int $documentoId): array
    {
        if ($origen !== 'compra') {
            return [];
        }

        return DB::table('retencion_lineas as l')
            ->join('retenciones as r', 'r.id', '=', 'l.retencion_id')
            ->where('r.empresa_id', $empresaId)
            ->where('r.purchase_id', $documentoId)
            ->where('l.tipo', 'renta')
            ->get(['l.codigo_sri', 'l.base', 'l.porcentaje', 'l.valor'])
            ->map(fn ($l) => [
                'codRetAir'    => $l->codigo_sri ?? '332',
                'baseImpAir'   => $this->monto($l->base),
                'porcentajeAir' => $this->monto($l->porcentaje),
                'valRetAir'    => $this->monto($l->valor),
            ])->all();
    }

    /** Las ventas, agrupadas por cliente: así las pide el anexo. */
    private function ventas(int $empresaId, Carbon $desde, Carbon $hasta, array &$avisos): array
    {
        $filas = DB::table('sales as v')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->where('v.empresa_id', $empresaId)
            ->where('v.estado', 'confirmado')
            ->whereBetween('v.fecha', [$desde, $hasta])
            ->groupBy('c.tipo_identificacion', 'c.numero_identificacion', 'c.tipo_persona')
            ->selectRaw('c.tipo_identificacion, c.numero_identificacion, c.tipo_persona,
                         count(*) as comprobantes, sum(v.subtotal) as subtotal, sum(v.iva) as iva')
            ->get();

        $registros = [];

        foreach ($filas as $f) {
            // En el ERP el consumidor final es un cliente más, con la
            // identificación que el SRI reserva para él.
            $esConsumidorFinal = ! $f->numero_identificacion
                || $f->numero_identificacion === self::CONSUMIDOR_FINAL
                || $f->tipo_identificacion === 'consumidor_final';

            $tipoId = $this->catalogo->codigo(
                'tipo_identificacion',
                'venta:' . ($esConsumidorFinal ? 'consumidor_final' : ($f->tipo_identificacion ?? 'cedula')),
                'ats', '2', $empresaId,
            );

            $registros[] = [
                'tpIdCliente'   => $tipoId ?? '07',
                'idCliente'     => $esConsumidorFinal ? self::CONSUMIDOR_FINAL : $this->soloDigitos($f->numero_identificacion),
                'parteRelVtas'  => 'NO',
                'tipoComprobante' => '18',
                'tipoEmision'   => 'F',
                'numeroComprobantes' => (string) $f->comprobantes,
                'baseNoGraIva'  => '0.00',
                'baseImponible' => $this->monto((float) $f->iva > 0 ? 0 : $f->subtotal),
                'baseImpGrav'   => $this->monto((float) $f->iva > 0 ? $f->subtotal : 0),
                'montoIva'      => $this->monto($f->iva),
                'valorRetIva'   => '0.00',
                'valorRetRenta' => '0.00',
            ];
        }

        if ($registros === []) {
            $avisos[] = 'No hay ventas confirmadas en el período: el anexo saldrá solo con compras.';
        }

        return $registros;
    }

    /** "001-001-000000123" → sus tres partes. Sin ese formato, el SRI rechaza. */
    private function partirNumero(?string $numero): array
    {
        $partes = explode('-', (string) $numero);

        return count($partes) === 3
            ? ['estab' => str_pad($partes[0], 3, '0', STR_PAD_LEFT),
               'punto' => str_pad($partes[1], 3, '0', STR_PAD_LEFT),
               'secuencial' => str_pad($partes[2], 9, '0', STR_PAD_LEFT)]
            : ['estab' => '001', 'punto' => '001',
               'secuencial' => str_pad($this->soloDigitos($numero) ?: '1', 9, '0', STR_PAD_LEFT)];
    }

    private function soloDigitos(?string $v): string
    {
        return preg_replace('/\D/', '', (string) $v) ?: '';
    }

    private function monto($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    /**
     * La razón social como consta en el SRI (RIVETECUADOR S.A.S.), que no siempre es el
     * nombre con que se creó la empresa en el ERP. Sale de la última declaración leída
     * del portal; sin ninguna, se usa el nombre de la empresa.
     */
    private function razonSocialDelSri(int $empresaId): ?string
    {
        $razon = \App\Models\Declaracion::where('empresa_id', $empresaId)
            ->whereNotNull('datos->cabecera->razon_social')
            ->latest('updated_at')->first()?->datos['cabecera']['razon_social'] ?? null;

        return filled($razon) ? trim((string) $razon) : null;
    }
}
