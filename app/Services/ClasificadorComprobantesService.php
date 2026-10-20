<?php

namespace App\Services;

use App\Models\ComprobanteSri;
use App\Models\Gasto;
use App\Models\GastoLinea;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\TipoGasto;
use App\Modules\Compras\Actions\ProveedorPorIdentificacion;
use App\Modules\Ventas\Actions\ClientePorIdentificacion;
use Illuminate\Support\Facades\DB;

/**
 * Convierte un comprobante del portal en contabilidad de verdad.
 *
 * El listado del SRI dice quién facturó y por cuánto, no qué se compró. Hasta
 * que alguien lo diga, esa factura suma al 104 y no existe en el balance. Al
 * clasificarla se crea el documento que corresponde y con él su asiento:
 *
 *   inventario   → una compra, que mueve el kardex y recalcula el promedio
 *   activo fijo  → un ítem de inventario con su vida útil, que se depreciará
 *   gasto        → un gasto con su IVA como crédito tributario
 *   no deducible → igual, pero marcado para la conciliación de la renta
 *
 * De ahí que existan dos puertas para que algo entre al inventario: el módulo
 * de bodega, o una factura que llegó del portal. La segunda es la que salva
 * los meses que nadie registró en su momento.
 *
 * Una factura emitida que no está en el ERP entra igual, como venta: con su
 * cliente, su ingreso, su IVA y la descarga del costo del producto vendido.
 *
 * Antes de registrar se pide al SRI el XML por la clave de acceso: el listado
 * del portal no separa la base gravada de la de tarifa cero, el XML sí. Y la
 * clave queda guardada en el documento, que es la autorización que pide el ATS.
 * Cómo se pagó (caja, banco, tarjeta o a crédito) lo dice quien registra: el
 * SRI no lo sabe (ver MedioPago).
 *
 * Marco: skills `contabilidad-ec` e `inventarios-contable-ec`.
 */
class ClasificadorComprobantesService
{
    public const DESTINOS = [
        'inventario'   => 'Compra de inventario',
        'activo_fijo'  => 'Activo fijo',
        'gasto'        => 'Gasto',
        'no_deducible' => 'Gasto no deducible',
    ];

    /**
     * Clasifica un comprobante y crea su documento contable.
     *
     * @param  array{destino?: string, tipo_gasto_id?: int, inventory_item_id?: int, cantidad?: float, pago?: string}  $datos
     * @return array{ok: bool, documento?: string, id?: int, error?: string}
     */
    public function clasificar(ComprobanteSri $c, array $datos, ?int $usuarioId = null): array
    {
        if ($c->conciliado_con) {
            return ['ok' => false, 'error' => 'Ese comprobante ya está registrado en el ERP.'];
        }

        $destino = $c->origen === 'emitido' ? 'venta' : $this->destino($c, $datos);

        if ($destino !== 'venta' && ! array_key_exists((string) $destino, self::DESTINOS)) {
            return ['ok' => false, 'error' => 'Hay que decir qué fue esa factura.'];
        }

        // El período cerrado no admite documentos nuevos, aquí tampoco.
        try {
            app(ContabilidadService::class)->exigirEjercicioAbierto(
                $c->empresa_id, $c->fecha_emision->toDateString(),
            );
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        // El desglose exacto, fuera de la transacción: es una llamada al SRI.
        $this->exacto($c);

        return DB::transaction(function () use ($c, $datos, $destino, $usuarioId) {
            $resultado = match ($destino) {
                'venta'                     => $this->comoVenta($c, $datos),
                'inventario', 'activo_fijo' => $this->comoCompra($c, $datos, $destino),
                default                     => $this->comoGasto($c, $datos, $destino === 'no_deducible'),
            };

            if (! ($resultado['ok'] ?? false)) {
                return $resultado;
            }

            $c->update([
                'destino'           => $destino,
                'tipo_gasto_id'     => $datos['tipo_gasto_id'] ?? null,
                'inventory_item_id' => $datos['inventory_item_id'] ?? null,
                'cantidad'          => $datos['cantidad'] ?? null,
                'pago'              => $datos['pago'] ?? 'credito',
                'clasificado_en'    => now(),
                'clasificado_por'   => $usuarioId,
                'conciliado_con'    => $resultado['documento'],
                'conciliado_id'     => $resultado['id'],
            ]);

            return $resultado;
        });
    }

    /**
     * Qué fue esa factura.
     *
     * Si entra a un ítem del inventario, **el ítem ya lo dice**: el formulario
     * de bodega obliga a decir si es materia prima, insumo, producto terminado
     * o activo fijo. Volver a preguntarlo aquí sería pedir dos veces el mismo
     * dato, y con dos respuestas que pueden contradecirse. Solo se pregunta por
     * lo que no está registrado en ninguna parte: los gastos.
     */
    private function destino(ComprobanteSri $c, array $datos): ?string
    {
        if ($itemId = $datos['inventory_item_id'] ?? null) {
            $item = InventoryItem::withoutGlobalScopes()
                ->where('empresa_id', $c->empresa_id)->find($itemId);

            if ($item) {
                return $item->type === 'activo_fijo' ? 'activo_fijo' : 'inventario';
            }
        }

        return $datos['destino'] ?? null;
    }

    /**
     * La factura entra como compra: eso mueve el kardex, recalcula el costo
     * promedio y genera el asiento, todo por el camino normal del ERP.
     */
    private function comoCompra(ComprobanteSri $c, array $datos, string $destino): array
    {
        $itemId = $datos['inventory_item_id'] ?? null;

        if (! $itemId) {
            return ['ok' => false, 'error' => 'Hay que decir a qué ítem del inventario entra.'];
        }

        $item = InventoryItem::withoutGlobalScopes()
            ->where('empresa_id', $c->empresa_id)->find($itemId);

        if (! $item) {
            return ['ok' => false, 'error' => 'Ese ítem no es de esta empresa.'];
        }

        // Sin cantidad no hay costo unitario, y sin costo unitario no hay kardex.
        $cantidad = (float) ($datos['cantidad'] ?? 0) ?: 1.0;
        $pago = MedioPago::atributos($datos['pago'] ?? null);
        $base = $c->base;
        $total = round($base + (float) $c->iva, 2);

        $compra = Purchase::withoutGlobalScopes()->create([
            'empresa_id'     => $c->empresa_id,
            'supplier_id'    => $this->proveedor($c)?->id,
            'numero_factura' => $c->numero,
            'date'           => $c->fecha_emision,
            'subtotal'       => $base,
            'iva'            => $c->iva,
            'total'          => $total,
            // El check de la tabla solo admite contado, credito_local y credito_exterior.
            'tipo_pago'      => $pago['forma_pago'] === 'credito' ? 'credito_local' : 'contado',
            'status'         => 'borrador',
            // La autorización de una factura electrónica es su clave de acceso: la pide el ATS.
            'clave_acceso'   => $c->clave_acceso,
            'notas'          => 'Del portal del SRI · ' . $c->razon_social . $this->diferencia($c, $total),
        ] + $pago);

        // Una línea por tarifa: el ATS separa la base gravada de la de tarifa cero.
        foreach ($this->partes($c) as [$parte, $ivaParte]) {
            $cantidadParte = round($cantidad * $parte / max($base, 0.0001), 4);

            DB::table('purchase_items')->insert([
                'purchase_id'       => $compra->id,
                'inventory_item_id' => $item->id,
                'quantity'          => $cantidadParte,
                'unit_price'        => round($parte / max($cantidadParte, 0.0001), 4),
                'aplica_iva'        => $ivaParte > 0,
                'subtotal'          => $parte,
                'iva_monto'         => $ivaParte,
                'total_item'        => round($parte + $ivaParte, 2),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Confirmar es lo que dispara el asiento y el movimiento de kardex.
        $compra->update(['status' => 'confirmado']);
        $compra->refresh();

        if (! $compra->journal_entry_id) {
            return ['ok' => false, 'error' => 'La compra se creó pero no generó asiento: '
                . ($compra->error_contable_msg ?: 'revisa la cuenta contable del ítem.')];
        }

        return ['ok' => true, 'documento' => 'purchase', 'id' => $compra->id,
                'detalle' => "compra {$compra->numero_factura} · {$item->nombre}"];
    }

    /** La factura entra como gasto, con su IVA como crédito tributario. */
    private function comoGasto(ComprobanteSri $c, array $datos, bool $noDeducible): array
    {
        $tipo = TipoGasto::find($datos['tipo_gasto_id'] ?? null);

        if (! $tipo) {
            return ['ok' => false, 'error' => 'Hay que decir qué tipo de gasto fue.'];
        }

        // Los tipos son globales; la cuenta sale del plan de esta empresa.
        $cuenta = $tipo->cuentaEn($c->empresa_id);

        if (! $cuenta) {
            return ['ok' => false, 'error' => "El tipo «{$tipo->nombre}» no encuentra su cuenta "
                . "({$tipo->codigo_cuenta}) en el plan de esta empresa."];
        }

        $gasto = Gasto::create([
            'empresa_id'       => $c->empresa_id,
            'supplier_id'      => $this->proveedor($c)?->id,
            'numero_documento' => $c->numero,
            'autorizacion'     => $c->clave_acceso,
            'tipo_documento'   => 'factura',
            'fecha'            => $c->fecha_emision,
            'descripcion'      => $tipo->nombre . ' · ' . $c->razon_social,
            'estado'           => 'borrador',
        ] + MedioPago::atributos($datos['pago'] ?? null));

        // Una línea por tarifa, con su propio porcentaje de IVA.
        foreach ($this->partes($c) as [$parte, $ivaParte]) {
            GastoLinea::create([
                'gasto_id'        => $gasto->id,
                'tipo_gasto_id'   => $tipo->id,
                'account_plan_id' => $cuenta,
                'descripcion'     => 'Del portal del SRI' . ($ivaParte > 0 ? '' : ' · tarifa 0 %'),
                'base'            => $parte,
                'porcentaje_iva'  => $parte > 0 ? round($ivaParte / $parte * 100, 2) : 0,
                'iva'             => $ivaParte,
                'deducible'       => ! $noDeducible,
                'motivo_no_deducible' => $noDeducible ? 'Marcado al clasificar el comprobante' : null,
            ]);
        }

        $confirmado = app(GastoService::class)->confirmar($gasto->fresh('lineas'));

        return ['ok' => true, 'documento' => 'gasto', 'id' => $confirmado->id,
                'detalle' => "gasto {$c->numero} · {$tipo->nombre}"];
    }

    /**
     * La factura emitida entra como venta: con su cliente, su ingreso y su IVA,
     * y la salida del producto del kardex al costo promedio (el asiento de costo
     * lo genera el observer de ventas, igual que una venta del módulo).
     */
    private function comoVenta(ComprobanteSri $c, array $datos): array
    {
        $item = InventoryItem::withoutGlobalScopes()
            ->where('empresa_id', $c->empresa_id)->find($datos['inventory_item_id'] ?? null);

        if (! $item) {
            return ['ok' => false, 'error' => 'Hay que decir qué producto se vendió.'];
        }

        $cantidad = (float) ($datos['cantidad'] ?? 0) ?: 1.0;
        $pago = MedioPago::atributos($datos['pago'] ?? null);
        $cliente = app(ClientePorIdentificacion::class)->obtener(
            $c->empresa_id, $c->identificacion ?: ClientePorIdentificacion::CONSUMIDOR_FINAL, $c->razon_social,
        );

        $venta = Sale::withoutGlobalScopes()->create([
            'empresa_id'     => $c->empresa_id,
            'referencia'     => $c->numero,
            'fecha'          => $c->fecha_emision,
            'customer_id'    => $cliente->id,
            'tipo_venta'     => $pago['forma_pago'] === 'credito' ? 'credito' : 'contado',
            'tipo_operacion' => 'productos',
            'subtotal'       => 0,
            'iva'            => 0,
            'total'          => 0,
            'estado'         => 'borrador',
            'clave_acceso'   => $c->clave_acceso,
            'notas'          => 'Del portal del SRI · ' . ($c->razon_social ?: $c->identificacion),
        ] + $pago);

        foreach ($this->partes($c) as [$parte, $ivaParte]) {
            $cantidadParte = round($cantidad * $parte / max($c->base, 0.0001), 4);

            // El ítem calcula su IVA solo; aquí se dice si la línea lo lleva.
            SaleItem::create([
                'sale_id'           => $venta->id,
                'inventory_item_id' => $item->id,
                'tipo_item'         => 'producto',
                'cantidad'          => $cantidadParte,
                'precio_unitario'   => round($parte / max($cantidadParte, 0.0001), 4),
                'aplica_iva'        => $ivaParte > 0,
            ]);
        }

        $venta->refresh();
        if (abs((float) $venta->iva - (float) $c->iva) > 0.01) {
            // El ítem de venta calcula el IVA a la tarifa general; si el comprobante
            // trae otra tarifa o ICE, queda escrito para revisarlo.
            $venta->update(['notas' => $venta->notas . " · El comprobante dice IVA {$c->iva} y la venta calculó {$venta->iva}."]);
        }

        $venta->update(['estado' => 'confirmado']);
        $venta->refresh();

        if (! $venta->journal_entry_id) {
            return ['ok' => false, 'error' => 'La venta se creó pero no generó asiento: '
                . ($venta->error_contable_msg ?: 'revisa el mapeo contable del producto.')];
        }

        return ['ok' => true, 'documento' => 'sale', 'id' => $venta->id,
                'detalle' => "venta {$c->numero} · {$item->nombre}"];
    }

    /**
     * Pide al SRI el XML por la clave de acceso y deja el desglose exacto.
     *
     * El listado del portal trae la base total y el IVA; no dice cuánto fue a
     * tarifa cero (y el de emitidos ni siquiera trae la base). El XML sí. Si el
     * SRI no responde, se sigue con lo del listado: es un dato menos fino, no un error.
     */
    public function exacto(ComprobanteSri $c): void
    {
        if (! $c->clave_acceso || $c->desglose === 'xml') {
            return;
        }

        try {
            $r = app(ServicioSri::class)->autorizacionComprobante($c->clave_acceso);
        } catch (\Throwable) {
            return;
        }

        if (! ($r['ok'] ?? false) || ($r['estado'] ?? '') !== 'AUTORIZADO') {
            return;
        }

        $xml = $r['comprobante'];
        $c->update(ComprobanteSri::basesDesdeXml($xml['impuestos'] ?? []) + [
            'total'    => round((float) ($xml['importe_total'] ?? $c->total), 2),
            'desglose' => 'xml',
        ]);
        $c->refresh();
    }

    /**
     * La factura partida por tarifa: [[base, iva], ...]. Sin desglose, una sola parte.
     *
     * @return array<int, array{0: float, 1: float}>
     */
    private function partes(ComprobanteSri $c): array
    {
        $partes = array_values(array_filter([
            [round((float) $c->base_gravada, 2), round((float) $c->iva, 2)],
            [round((float) $c->base_cero, 2), 0.0],
        ], fn ($p) => $p[0] > 0));

        return $partes ?: [[$c->base, round((float) $c->iva, 2)]];
    }

    /** Si el total del comprobante no es base + IVA (ICE, propina), queda anotado. */
    private function diferencia(ComprobanteSri $c, float $total): string
    {
        return abs((float) $c->total - $total) > 0.009
            ? " · El comprobante suma {$c->total} y base + IVA dan {$total} (ICE u otro rubro)."
            : '';
    }

    /** El proveedor del portal, creado si no estaba. */
    private function proveedor(ComprobanteSri $c): ?Supplier
    {
        if (! $c->identificacion) {
            return null;
        }

        return app(ProveedorPorIdentificacion::class)->obtener($c->empresa_id, $c->identificacion, $c->razon_social);
    }

    /**
     * Lo que se propone para un comprobante, mirando cómo se clasificó antes
     * a ese mismo proveedor.
     *
     * Con veintinueve facturas del mismo proveedor, decirlo una vez debería
     * bastar para las otras veintiocho.
     *
     * @return array{destino: string|null, tipo_gasto_id: int|null, inventory_item_id: int|null, pago: string|null, visto: int}
     */
    public function proponer(ComprobanteSri $c): array
    {
        $anterior = ComprobanteSri::where('empresa_id', $c->empresa_id)
            ->where('identificacion', $c->identificacion)
            ->whereNotNull('destino')
            ->latest('clasificado_en')
            ->first();

        $vistos = ComprobanteSri::where('empresa_id', $c->empresa_id)
            ->where('identificacion', $c->identificacion)
            ->whereNotNull('destino')->count();

        return [
            'destino'           => $anterior?->destino,
            'tipo_gasto_id'     => $anterior?->tipo_gasto_id,
            'inventory_item_id' => $anterior?->inventory_item_id,
            'pago'              => $anterior?->pago,
            'visto'             => $vistos,
        ];
    }

    /**
     * Clasifica de golpe todos los comprobantes pendientes de un proveedor.
     *
     * @return array{hechos: int, fallos: array<int, string>}
     */
    public function clasificarProveedor(int $empresaId, string $identificacion, array $datos, ?int $usuarioId = null): array
    {
        $pendientes = ComprobanteSri::where('empresa_id', $empresaId)
            ->where('identificacion', $identificacion)
            ->sinClasificar()->where('origen', 'recibido')->get();

        $hechos = 0;
        $fallos = [];

        foreach ($pendientes as $c) {
            // La cantidad se reparte por comprobante: cada factura trae la suya.
            $suyo = $datos;

            if (($datos['inventory_item_id'] ?? null) && empty($datos['cantidad'])) {
                $suyo['cantidad'] = 1;
            }

            $r = $this->clasificar($c, $suyo, $usuarioId);

            if ($r['ok'] ?? false) {
                $hechos++;
            } else {
                $fallos[] = "{$c->numero}: {$r['error']}";
            }
        }

        return ['hechos' => $hechos, 'fallos' => $fallos];
    }

    /**
     * Cuántas facturas esperan en la bandeja.
     *
     * Solo las recibidas: una emitida es una venta y no se clasifica aquí. Si
     * se contaran, el menú diría un número y la bandeja mostraría otro.
     */
    public function pendientesVentas(int $empresaId): int
    {
        return ComprobanteSri::where('empresa_id', $empresaId)->where('origen', 'emitido')->sinClasificar()->count();
    }

    public function pendientes(int $empresaId, ?int $anio = null, ?int $mes = null): int
    {
        return ComprobanteSri::where('empresa_id', $empresaId)
            ->where('origen', 'recibido')
            ->when($anio && $mes, fn ($q) => $q->delPeriodo($anio, $mes))
            ->sinClasificar()->count();
    }
}
