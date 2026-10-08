<?php

namespace App\Modules\Compras\Actions;

use App\Models\ComprobanteSri;
use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\ProductoProveedor;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\ServicioSri;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Registra como compra una factura electrónica que llegó por correo (o por la
 * clave de acceso de una foto). El XML lo lee el microservicio del SRI; aquí se
 * decide si es de esta empresa, si ya estaba, y qué es cada línea.
 *
 * - Cada producto del proveedor se recuerda en productos_proveedor. Si ya se
 *   sabe qué es (ítem de inventario o tipo de gasto), la línea entra sola; si no,
 *   queda "por configurar" y se avisa en Inventario.
 * - La forma de pago se toma del XML cuando no deja dudas (crédito con plazo, o
 *   un único medio de ese tipo en la empresa); si no, se pregunta.
 * - Con las dos cosas resueltas la compra se confirma: el observer genera el
 *   asiento y la entrada al kardex, que recalcula el costo promedio.
 *
 * Marco: skills `comprobantes-electronicos-sri`, `contabilidad-ec`, `inventarios-contable-ec`.
 */
#[Documentado(
    grupo: 'Compras',
    descripcion: 'Convierte una factura electrónica recibida en una compra del ERP, con proveedor, líneas y forma de pago.',
    tipo: 'action',
)]
final class RegistrarCompraElectronica
{
    /** Tabla 17 del SRI: códigos de IVA sin tarifa (0 %, no objeto, exento). */
    private const IVA_SIN_TARIFA = ['0', '6', '7'];

    public function __construct(
        private readonly ServicioSri $sri,
        private readonly ProveedorPorIdentificacion $proveedores,
        private readonly ConfirmarCompraElectronica $confirmar,
        private readonly NotificarCompra $notificar,
    ) {}

    /**
     * @return array{ok: bool, estado: string, mensaje: string, compra_id?: int}
     *   estado: registrada | pendiente | repetida | rechazada
     */
    public function desdeXml(Empresa $empresa, string $xml, string $origen = Purchase::ORIGEN_CORREO): array
    {
        $lectura = $this->sri->leerXmlComprobante($xml);
        if (! ($lectura['ok'] ?? false)) {
            return $this->rechazo($lectura['error'] ?? 'El XML no se pudo leer.');
        }

        // Una factura suelta no dice si el SRI la autorizó: se le pregunta por su clave.
        if (($lectura['estado'] ?? '') !== 'AUTORIZADO') {
            $clave = $lectura['comprobante']['clave_acceso'] ?? null;
            if (! $clave) {
                return $this->rechazo('El XML no trae clave de acceso.');
            }
            $lectura = $this->sri->autorizacionComprobante($clave);
        }

        return $this->registrar($empresa, $lectura, $origen, $xml);
    }

    /** Para la foto del RIDE: con la clave se pide al SRI el XML original. */
    public function desdeClave(Empresa $empresa, string $clave, string $origen = Purchase::ORIGEN_TELEGRAM): array
    {
        return $this->registrar($empresa, $this->sri->autorizacionComprobante($clave), $origen, null);
    }

    private function registrar(Empresa $empresa, array $lectura, string $origen, ?string $xml): array
    {
        if (! ($lectura['ok'] ?? false)) {
            return $this->rechazo($lectura['error'] ?? 'El SRI no devolvió el comprobante.');
        }
        if (($lectura['estado'] ?? '') !== 'AUTORIZADO') {
            return $this->rechazo('El SRI no tiene autorizado ese comprobante.');
        }

        $c = $lectura['comprobante'];
        $clave = (string) $c['clave_acceso'];

        if ($error = $this->validar($empresa, $c, $clave)) {
            return $this->rechazo($error);
        }

        $repetida = Purchase::withoutGlobalScopes()->where('empresa_id', $empresa->id)
            ->where(fn ($q) => $q->where('clave_acceso', $clave)
                ->orWhere(fn ($q) => $q->where('numero_factura', $c['numero'])
                    ->whereHas('supplier', fn ($s) => $s->where('numero_identificacion', $c['emisor']['ruc']))))
            ->first();
        if ($repetida || $this->yaConciliada($empresa->id, $clave)) {
            return ['ok' => true, 'estado' => 'repetida', 'compra_id' => $repetida?->id,
                    'mensaje' => "La factura {$c['numero']} ya estaba registrada."];
        }

        $compra = DB::transaction(fn () => $this->crear($empresa, $c, $clave, $origen, $xml));

        $this->confirmar->intentar($compra);
        $compra->refresh();

        $this->notificar->recibida($compra);

        return [
            'ok'        => true,
            'estado'    => $compra->status === Purchase::CONFIRMADO ? 'registrada' : 'pendiente',
            'compra_id' => $compra->id,
            'mensaje'   => $this->notificar->resumen($compra),
        ];
    }

    private function validar(Empresa $empresa, array $c, string $clave): ?string
    {
        if (($c['tipo'] ?? '') !== 'factura') {
            return 'Por ahora solo se registran facturas (llegó: ' . ($c['tipo'] ?? 'desconocido') . ').';
        }
        // Posición 24 de la clave: 1 pruebas, 2 producción. Una de pruebas no vale tributariamente.
        if (substr($clave, 23, 1) !== '2') {
            return 'Es un comprobante del ambiente de pruebas del SRI: no tiene valor tributario.';
        }
        $comprador = (string) ($c['comprador']['identificacion'] ?? '');
        if ($comprador !== (string) $empresa->numero_identificacion) {
            return "La factura es para {$comprador} y la empresa es {$empresa->numero_identificacion}.";
        }

        return null;
    }

    private function yaConciliada(int $empresaId, string $clave): bool
    {
        return ComprobanteSri::where('empresa_id', $empresaId)->where('clave_acceso', $clave)
            ->whereNotNull('conciliado_con')->exists();
    }

    private function crear(Empresa $empresa, array $c, string $clave, string $origen, ?string $xml): Purchase
    {
        $emisor = $c['emisor'];
        $proveedor = $this->proveedores->obtener($empresa->id, $emisor['ruc'], $emisor['razon_social'], [
            'nombre_comercial' => $emisor['nombre_comercial'] ?? null,
            'direccion'        => $emisor['direccion'] ?? null,
        ]);

        $fecha = Carbon::createFromFormat('d/m/Y', $c['fecha_emision'])->startOfDay();
        $pago = $this->pagoPrincipal($c['pagos'] ?? []);

        $compra = Purchase::withoutGlobalScopes()->create([
            'empresa_id'     => $empresa->id,
            'supplier_id'    => $proveedor->id,
            'numero_factura' => $c['numero'],
            'date'           => $fecha,
            'subtotal'       => 0,
            'iva'            => 0,
            'total'          => 0,
            'status'         => Purchase::BORRADOR,
            'origen'         => $origen,
            'clave_acceso'   => $clave,
            'xml_path'       => $xml ? $this->guardarXml($empresa->id, $clave, $xml) : null,
            'forma_pago_sri' => $pago['codigo'] ?? null,
            'plazo_dias'     => (int) ($pago['plazo'] ?? 0) ?: null,
            'tipo_pago'      => 'contado',
            'forma_pago'     => 'transferencia',
            'requiere_forma_pago' => true,
            'notas'          => 'Factura electrónica · ' . $emisor['razon_social'],
        ]);

        [$subtotal, $iva] = $this->lineas($compra, $proveedor->id, $c['detalles'] ?? []);

        $total = round($subtotal + $iva, 2);
        $importe = round((float) $c['importe_total'], 2);
        $notas = $compra->notas;
        if (abs($total - $importe) > 0.009) {
            // Propina, ICE u otro rubro que no está en las líneas: queda escrito para revisar.
            $notas .= " · El XML dice {$importe} y las líneas suman {$total}.";
        }

        $compra->update(['subtotal' => $subtotal, 'iva' => $iva, 'total' => $total, 'notas' => $notas]
            + app(ResolverFormaPago::class)->desdeXml($compra, $pago));

        $this->registrarComprobante($empresa->id, $c, $clave, $compra);

        return $compra;
    }

    /** @return array{0: float, 1: float} subtotal e IVA de la compra */
    private function lineas(Purchase $compra, int $proveedorId, array $detalles): array
    {
        $subtotal = 0.0;
        $iva = 0.0;

        foreach ($detalles as $d) {
            $codigo = trim((string) ($d['codigo'] ?? '')) ?: Str::limit(Str::slug($d['descripcion']), 100, '');
            $producto = $this->producto($compra->empresa_id, $proveedorId, $codigo, $d);

            $base = round((float) $d['subtotal'], 2);
            $ivaLinea = 0.0;
            $otros = 0.0;
            foreach ($d['impuestos'] ?? [] as $imp) {
                // IVA (código 2) es crédito tributario; ICE u otros, costo de la línea.
                if (($imp['codigo'] ?? '') === '2') {
                    $ivaLinea += in_array($imp['codigo_porcentaje'] ?? '', self::IVA_SIN_TARIFA, true) ? 0 : (float) $imp['valor'];
                } else {
                    $otros += (float) $imp['valor'];
                }
            }
            $base = round($base + $otros, 2);
            $cantidad = (float) $d['cantidad'] ?: 1.0;

            $linea = new PurchaseItem([
                'purchase_id'           => $compra->id,
                'inventory_item_id'     => $producto->inventory_item_id,
                'producto_proveedor_id' => $producto->id,
                'descripcion'           => Str::limit($d['descripcion'], 300, ''),
                'codigo_proveedor'      => $codigo,
                'quantity'              => $cantidad,
                // Con el descuento ya aplicado: es el costo real de cada unidad.
                'unit_price'            => round($base / $cantidad, 4),
                'aplica_iva'            => $ivaLinea > 0,
                'subtotal'              => $base,
                'iva_monto'             => round($ivaLinea, 2),
                'total_item'            => round($base + $ivaLinea, 2),
            ]);
            $linea->save();

            $subtotal += $base;
            $iva += round($ivaLinea, 2);
        }

        return [round($subtotal, 2), round($iva, 2)];
    }

    /**
     * Lo que ya se sabe de ese producto del proveedor. La primera vez se intenta
     * reconocer en el inventario por código o por nombre idéntico; si no, queda
     * por configurar.
     */
    private function producto(int $empresaId, int $proveedorId, string $codigo, array $d): ProductoProveedor
    {
        $producto = ProductoProveedor::withoutGlobalScopes()->firstOrNew([
            'empresa_id' => $empresaId, 'supplier_id' => $proveedorId, 'codigo' => $codigo,
        ]);

        $producto->descripcion = Str::limit($d['descripcion'], 300, '');
        $producto->ultimo_precio = $d['precio_unitario'];

        if (! $producto->exists && ! $producto->estaConfigurado()) {
            $item = InventoryItem::withoutGlobalScopes()->where('empresa_id', $empresaId)
                ->where('activo', true)
                ->where(fn ($q) => $q->where('codigo', $codigo)
                    ->orWhereRaw('lower(nombre) = ?', [mb_strtolower(trim($d['descripcion']))]))
                ->first();
            if ($item) {
                $producto->inventory_item_id = $item->id;
                $producto->configurado_en = now();
            }
        }

        $producto->save();

        return $producto;
    }

    /** El pago de mayor valor: es el que define cómo se pagó la factura. */
    private function pagoPrincipal(array $pagos): array
    {
        return collect($pagos)->sortByDesc(fn ($p) => (float) ($p['total'] ?? 0))->first() ?? [];
    }

    private function guardarXml(int $empresaId, string $clave, string $xml): string
    {
        $ruta = "comprobantes/{$empresaId}/recibidos/{$clave}.xml";
        Storage::disk('local')->put($ruta, $xml);

        return $ruta;
    }

    /**
     * Deja el comprobante en comprobantes_sri ya conciliado con la compra: así el
     * listado del portal que se importe después no la vuelve a pedir ni la cuenta
     * dos veces.
     */
    private function registrarComprobante(int $empresaId, array $c, string $clave, Purchase $compra): void
    {
        $baseGravada = 0.0;
        $baseCero = 0.0;
        $iva = 0.0;
        foreach ($c['impuestos'] ?? [] as $imp) {
            if (($imp['codigo'] ?? '') !== '2') {
                continue;
            }
            if (in_array($imp['codigo_porcentaje'] ?? '', self::IVA_SIN_TARIFA, true)) {
                $baseCero += (float) $imp['base_imponible'];
            } else {
                $baseGravada += (float) $imp['base_imponible'];
                $iva += (float) $imp['valor'];
            }
        }

        [$establecimiento, $punto, $secuencial] = array_pad(explode('-', $c['numero']), 3, null);

        ComprobanteSri::updateOrCreate(
            ['empresa_id' => $empresaId, 'clave_acceso' => $clave],
            [
                'origen'           => 'recibido',
                'tipo_comprobante' => '01',
                'identificacion'   => $c['emisor']['ruc'],
                'razon_social'     => $c['emisor']['razon_social'],
                'fecha_emision'    => Carbon::createFromFormat('d/m/Y', $c['fecha_emision'])->toDateString(),
                'numero'           => $c['numero'],
                'establecimiento'  => $establecimiento,
                'punto_emision'    => $punto,
                'secuencial'       => $secuencial,
                'base_gravada'     => round($baseGravada, 2),
                'base_cero'        => round($baseCero, 2),
                'iva'              => round($iva, 2),
                'total'            => round((float) $c['importe_total'], 2),
                'desglose'         => 'xml',
                'conciliado_con'   => 'purchase',
                'conciliado_id'    => $compra->id,
                'importado_en'     => now(),
            ],
        );
    }

    private function rechazo(string $mensaje): array
    {
        return ['ok' => false, 'estado' => 'rechazada', 'mensaje' => $mensaje];
    }
}
