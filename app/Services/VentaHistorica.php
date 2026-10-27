<?php

namespace App\Services;

use App\Models\AccountPlan;
use App\Models\ComprobanteSri;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\StoreOrder;
use App\Models\StoreOrderHistorial;
use App\Models\StoreProduct;

/**
 * Registro de una venta de un mes anterior al inicio de gestión (modo de cuadre).
 *
 * Esas ventas se registran de atrás hacia adelante: no hubo producción en el ERP,
 * así que lo vendido entra primero como saldo y después sale con la venta. Así
 * quedan el kardex, el costo de ventas y el pedido con su historial, y el mes
 * cuadra con lo presentado. Los insumos no se mueven.
 *
 * El costo del saldo es el precio de la factura con un margen del 30 %: es una
 * decisión solo para este registro, porque la mayoría de productos aún no tiene fórmula.
 */
class VentaHistorica
{
    public const MARGEN = 0.30;

    /** Cuenta contra la que entra el saldo: era mercadería que ya existía. */
    private const CUENTA_SALDO = '3.6.01';

    /** Los pasos que recorre un pedido entregado. */
    private const FLUJO = ['pendiente', 'pagado', 'receptado', 'procesando', 'enviado', 'entregado'];

    /** Entra como saldo la cantidad de la factura, al costo del margen, con su asiento. */
    public function saldo(ComprobanteSri $c, InventoryItem $item, float $cantidad, float $precio): void
    {
        $costo = round($precio * (1 - self::MARGEN), 4);
        $total = round($costo * $cantidad, 4);
        $fecha = $c->fecha_emision->toDateString();

        $patrimonio = AccountPlan::withoutGlobalScopes()->where('empresa_id', $c->empresa_id)
            ->where('code', self::CUENTA_SALDO)->first()
            ?? throw new \RuntimeException('Falta la cuenta ' . self::CUENTA_SALDO . ' (Ganancias acumuladas) en el plan de cuentas.');
        $inventario = AccountingService::getMapeo($c->empresa_id, $item->type, 'compra_contado');

        $asiento = JournalEntry::create([
            'empresa_id'      => $c->empresa_id,
            'fecha'           => $fecha,
            'descripcion'     => "Saldo de {$item->nombre} para la factura {$c->numero} (cuadre de gestión anterior)",
            'tipo'            => 'ajuste',
            'origen'          => 'automatico',
            'referencia_tipo' => 'saldo_cuadre',
            'referencia_id'   => $c->id,
            'status'          => 'confirmado',
            'total_debe'      => $total,
            'total_haber'     => $total,
            'esta_cuadrado'   => true,
            'confirmado_por'  => auth()->id(),
            'confirmado_at'   => now(),
        ]);
        $asiento->lines()->create(['account_plan_id' => $inventario->id, 'debe' => $total, 'haber' => 0, 'orden' => 1,
                                   'descripcion' => "Saldo {$item->nombre}"]);
        $asiento->lines()->create(['account_plan_id' => $patrimonio->id, 'debe' => 0, 'haber' => $total, 'orden' => 2,
                                   'descripcion' => "Saldo {$item->nombre}"]);

        // Promedio ponderado: el saldo es una entrada.
        $cantidadFinal = round((float) $item->stock_actual + $cantidad, 4);
        $valorFinal = round((float) $item->saldo_valorado + $total, 4);
        $promedio = $cantidadFinal > 0 ? round($valorFinal / $cantidadFinal, 10) : 0;

        InventoryMovement::create([
            'empresa_id'        => $c->empresa_id,
            'inventory_item_id' => $item->id,
            'type'              => 'entrada',
            'quantity'          => $cantidad,
            'unit_price'        => $costo,
            'total'             => $total,
            'reference_type'    => 'saldo_cuadre',
            'reference_id'      => $c->id,
            'journal_entry_id'  => $asiento->id,
            'motivo'            => 'saldo_cuadre',
            'notes'             => "Saldo para la factura {$c->numero}",
            'date'              => $fecha,
            'saldo_cantidad'    => $cantidadFinal,
            'costo_promedio'    => $promedio,
            'saldo_valor'       => $valorFinal,
            'user_id'           => auth()->id(),
        ]);

        $item->forceFill(['stock_actual' => $cantidadFinal, 'saldo_valorado' => $valorFinal, 'costo_promedio' => $promedio])->save();
    }

    /**
     * El pedido de esa venta, entregado, con todo su flujo en el historial a la fecha de la factura.
     *
     * @param  array<int, array{0: InventoryItem, 1: float, 2: float, 3: bool}>  $lineas
     */
    public function pedido(ComprobanteSri $c, Sale $venta, Customer $cliente, array $lineas): StoreOrder
    {
        $momento = $c->fecha_emision->copy()->setTime(12, 0);

        $pedido = new StoreOrder([
            'empresa_id'      => $c->empresa_id,
            'customer_id'     => $cliente->id,
            'origen'          => 'erp',
            'estado'          => 'entregado',
            'subtotal'        => (float) $venta->subtotal,
            'descuento'       => 0,
            'total'           => (float) $venta->total,
            'metodo_pago'     => $venta->forma_pago,
            'estado_pago'     => 'aprobado',
            'referencia_pago' => $c->numero,
            'direccion_envio' => [],
            'notas_cliente'   => "Registro histórico de la factura {$c->numero}",
            'sale_id'         => $venta->id,
        ]);
        $pedido->historialManual = true;
        $pedido->created_at = $momento;
        $pedido->updated_at = $momento;
        $pedido->save();

        foreach ($lineas as [$item, $cantidad, $precio]) {
            $producto = StoreProduct::withoutGlobalScopes()->where('empresa_id', $c->empresa_id)
                ->where('product_presentation_id', $item->product_presentation_id)->whereNotNull('product_presentation_id')->first()
                ?? throw new \RuntimeException("«{$item->nombre}» no tiene producto de tienda: créalo como presentación en Diseño de producto.");

            $pedido->orderItems()->create([
                'store_product_id'  => $producto->id,
                'inventory_item_id' => $item->id,
                'nombre_snapshot'   => $item->nombre,
                'precio_unitario'   => $precio,
                'cantidad'          => $cantidad,
                'subtotal'          => round($precio * $cantidad, 4),
            ]);
        }

        foreach (self::FLUJO as $estado) {
            StoreOrderHistorial::create([
                'empresa_id' => $c->empresa_id, 'store_order_id' => $pedido->id, 'estado' => $estado,
                'ocurrido_en' => $momento, 'user_id' => auth()->id(),
                'nota' => "Registro histórico desde la factura {$c->numero}",
            ]);
        }

        return $pedido;
    }
}
