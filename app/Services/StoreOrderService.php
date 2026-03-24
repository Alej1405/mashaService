<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Empresa;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreCoupon;
use App\Models\StoreCustomer;
use App\Models\StoreOrder;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

class StoreOrderService
{
    /**
     * Crea una orden pendiente de pago.
     * El flujo contable/inventario se activa solo al confirmar (confirmOrder).
     */
    public function createOrder(
        Empresa $empresa,
        StoreCustomer $customer,
        array $items,
        array $shippingAddress,
        ?string $couponCode = null,
        ?string $notes = null
    ): StoreOrder {
        return DB::transaction(function () use ($empresa, $customer, $items, $shippingAddress, $couponCode, $notes) {
            $subtotal   = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = StoreProduct::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->where('id', $item['store_product_id'])
                    ->where('publicado', true)
                    ->with('productDesign.inventoryItem')
                    ->firstOrFail();

                $qty          = (float) $item['cantidad'];
                $inventoryItem = $product->productDesign?->inventoryItem;
                $stock         = (float) ($inventoryItem?->stock_actual ?? 0);

                if ($inventoryItem && $stock < $qty) {
                    throw new \Exception("Stock insuficiente para: {$product->nombre} (disponible: {$stock})");
                }

                // Precio distribuidor si compra 10+ unidades
                $precioAplicado = ($qty >= 10 && (float) $product->precio_distribuidor > 0)
                    ? (float) $product->precio_distribuidor
                    : (float) $product->precio_venta;

                $lineTotal  = $precioAplicado * $qty;
                $subtotal  += $lineTotal;

                $orderItems[] = [
                    'store_product_id'  => $product->id,
                    'inventory_item_id' => $inventoryItem?->id,
                    'nombre_snapshot'   => $product->nombre,
                    'precio_unitario'   => $precioAplicado,
                    'cantidad'          => $qty,
                    'subtotal'          => $lineTotal,
                ];
            }

            // Cupón
            $descuento = 0;
            $coupon    = null;

            if ($couponCode) {
                $coupon = StoreCoupon::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->where('codigo', strtoupper($couponCode))
                    ->first();

                if ($coupon && $coupon->isValid($subtotal)) {
                    $descuento = $coupon->calcularDescuento($subtotal);
                }
            }

            $order = StoreOrder::create([
                'empresa_id'        => $empresa->id,
                'store_customer_id' => $customer->id,
                'estado'            => 'pendiente',
                'subtotal'          => $subtotal,
                'descuento'         => $descuento,
                'total'             => max(0, $subtotal - $descuento),
                'store_coupon_id'   => $coupon?->id,
                'estado_pago'       => 'pendiente',
                'direccion_envio'   => $shippingAddress,
                'notas_cliente'     => $notes,
            ]);

            foreach ($orderItems as $lineData) {
                $order->orderItems()->create($lineData);
            }

            if ($coupon) {
                $coupon->increment('usos_actuales');
            }

            return $order->load('orderItems');
        });
    }

    /**
     * Confirma una orden pagada:
     * 1. Resuelve o crea el cliente ERP genérico para ventas online.
     * 2. Crea la Sale en estado 'borrador'.
     * 3. Crea los SaleItems (SaleItem.saving() calcula subtotales).
     * 4. Actualiza Sale a 'confirmado' → dispara SaleObserver →
     *    AccountingService genera asiento + InventoryMovement reduce stock.
     */
    public function confirmOrder(StoreOrder $order): void
    {
        if ($order->sale_id) {
            return; // Ya confirmada anteriormente
        }

        DB::transaction(function () use ($order) {
            // ── Cliente ERP genérico para ventas online ──────────────────
            // Sales requiere customer_id NOT NULL. Usamos un cliente
            // "Consumidor Final" que representa todas las ventas online.
            $customer = Customer::withoutGlobalScopes()->firstOrCreate(
                [
                    'empresa_id'            => $order->empresa_id,
                    'numero_identificacion' => '9999999999',
                ],
                [
                    'codigo'               => 'CF-ONLINE',
                    'nombre'               => 'Consumidor Final — Ventas Online',
                    'tipo_persona'         => 'natural',
                    'tipo_identificacion'  => 'consumidor_final',
                    'activo'               => true,
                ]
            );

            // ── Sale en borrador (observer escucha 'updated' no 'created') ──
            $sale = Sale::create([
                'empresa_id'     => $order->empresa_id,
                'fecha'          => now()->toDateString(),
                'customer_id'    => $customer->id,
                'tipo_venta'     => 'contado',
                'tipo_operacion' => 'productos',
                'estado'         => 'borrador',
                'forma_pago'     => 'transferencia',  // online = transferencia bancaria
                'notas'          => "Venta online — Orden {$order->numero}",
            ]);

            // ── SaleItems: SaleItem.saving() calcula subtotal/iva/total ──
            foreach ($order->orderItems as $item) {
                if (!$item->inventory_item_id) continue; // sin inventario vinculado, se omite movimiento
                SaleItem::create([
                    'sale_id'           => $sale->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'tipo_item'         => 'producto',
                    'cantidad'          => $item->cantidad,
                    'precio_unitario'   => $item->precio_unitario,
                    'aplica_iva'        => false,
                ]);
            }

            // ── Confirmar: dispara SaleObserver → asiento + inventario ──
            // SaleObserver.updated() verifica isDirty('estado') === 'confirmado'
            $sale->update(['estado' => 'confirmado']);

            // ── Vincular orden ───────────────────────────────────────────
            $order->update([
                'sale_id'     => $sale->id,
                'estado'      => 'procesando',
                'estado_pago' => 'aprobado',
            ]);
        });
    }
}
