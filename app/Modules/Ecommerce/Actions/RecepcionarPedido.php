<?php

namespace App\Modules\Ecommerce\Actions;

use App\Models\InventoryMovement;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\DB;

/**
 * Recepción de un pedido (estado → receptado). Es AUTOMÁTICA: la dispara el
 * StoreOrderObserver cuando la tienda acepta el pedido. Nunca se gestiona a mano.
 *
 * Por cada línea: si hay stock suficiente en el inventario enlazado, lo descuenta
 * y deja el rastro en inventory_movements (salida). Lo que falte (sin inventario
 * enlazado o stock insuficiente) se MAPEA a producción vía GenerarOrdenProduccionPedido.
 *
 * NO toca contabilidad (reference_type='store_order' → el InventoryMovementObserver
 * lo ignora). Es idempotente.
 */
#[Documentado(
    grupo: 'Tienda',
    descripcion: 'Recepción automática de un pedido: descuenta el inventario disponible y mapea a producción lo que falte. Se dispara al pasar el pedido a receptado.',
    tipo: 'action',
)]
final class RecepcionarPedido
{
    public function __construct(private GenerarOrdenProduccionPedido $produccion) {}

    /**
     * @return array{descontados:int, faltantes:int}
     */
    public function handle(StoreOrder $order): array
    {
        return DB::transaction(function () use ($order) {
            // Idempotencia: si el pedido ya tiene movimientos, no se reprocesa.
            $yaProcesado = InventoryMovement::withoutGlobalScopes()
                ->where('empresa_id', $order->empresa_id)
                ->where('reference_type', 'store_order')
                ->where('reference_id', $order->id)
                ->exists();

            if ($yaProcesado) {
                return ['descontados' => 0, 'faltantes' => 0];
            }

            $order->loadMissing('orderItems.product.stockItems.inventoryItem');

            $descontados = 0;
            $faltantes   = [];

            foreach ($order->orderItems as $linea) {
                $resultado = $this->procesarLinea($order, $linea);
                $descontados += $resultado['movimientos'];
                if ($resultado['falta'] !== null) {
                    $faltantes[] = $resultado['falta'];
                }
            }

            // Lo que no se pudo cubrir con inventario se mapea a producción (stub).
            if ($faltantes !== []) {
                $this->produccion->handle($order, $faltantes);
            }

            return ['descontados' => $descontados, 'faltantes' => count($faltantes)];
        });
    }

    /**
     * @return array{movimientos:int, falta:?array{producto:string,cantidad:float,motivo:string}}
     */
    private function procesarLinea(StoreOrder $order, StoreOrderItem $linea): array
    {
        $producto = $linea->product;

        // Sin inventario enlazado = producto bajo pedido → producción.
        if (! $producto || $producto->stockItems->isEmpty()) {
            return [
                'movimientos' => 0,
                'falta' => ['producto' => $producto->nombre ?? 'N/D', 'cantidad' => (float) $linea->cantidad, 'motivo' => 'sin_inventario'],
            ];
        }

        // ¿Hay stock suficiente en TODOS los items enlazados?
        foreach ($producto->stockItems as $origen) {
            $requerido = (float) $linea->cantidad * ((float) $origen->cantidad ?: 1.0);
            $disponible = (float) ($origen->inventoryItem?->stock_actual ?? 0);
            if ($requerido > $disponible) {
                // Falta → no se descuenta la línea; se mapea a producción.
                return [
                    'movimientos' => 0,
                    'falta' => ['producto' => $producto->nombre, 'cantidad' => (float) $linea->cantidad, 'motivo' => 'stock_insuficiente'],
                ];
            }
        }

        // Suficiente → descuenta automáticamente de cada item enlazado.
        $movimientos = 0;
        foreach ($producto->stockItems as $origen) {
            $item = $origen->inventoryItem;
            $requerido = (float) $linea->cantidad * ((float) $origen->cantidad ?: 1.0);

            $item->decrement('stock_actual', $requerido);

            InventoryMovement::create([
                'empresa_id'        => $order->empresa_id,
                'inventory_item_id' => $item->id,
                'type'              => 'salida',
                'reference_type'    => 'store_order',
                'reference_id'      => $order->id,
                'quantity'          => $requerido,
                'unit_price'        => (float) ($item->purchase_price ?? 0),
                'total'             => (float) ($item->purchase_price ?? 0) * $requerido,
                'date'              => now()->toDateString(),
                'description'       => "Recepción pedido {$order->numero}",
            ]);

            $movimientos++;
        }

        return ['movimientos' => $movimientos, 'falta' => null];
    }
}
