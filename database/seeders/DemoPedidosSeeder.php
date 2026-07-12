<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\StoreProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pedidos de tienda de prueba para Rivet Ecuador (empresa 1). Ejercita el flujo
 * recepción→inventario del StoreOrderObserver / RecepcionarPedido:
 *
 *   - Pedidos que se pasan a 'receptado' → disparan el descuento automático de
 *     inventario (inventory_movements de salida, reference_type='store_order', que
 *     el contable IGNORA) y mapean a producción lo que no hay en stock.
 *   - Pedidos en otros estados ('pendiente','pagado') → NO tocan inventario.
 *
 * Casos cubiertos: recepción con stock suficiente, recepción con producto BAJO
 * PEDIDO (sin inventario), recepción con STOCK INSUFICIENTE, y pedidos abiertos.
 *
 * Idempotente: clave = (empresa_id, referencia_pago 'DEMO-...'). Si el pedido ya
 * existe NO se recrea ni se vuelve a descontar stock. Correr DESPUÉS de
 * DemoTiendaSeeder. Ejecutar: php artisan db:seed --class=DemoPedidosSeeder
 */
class DemoPedidosSeeder extends Seeder
{
    private const EMPRESA_ID = 1;

    public function run(): void
    {
        $empresaId = self::EMPRESA_ID;

        // Clientes reales de la empresa (los primeros disponibles).
        $clientes = Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $cli = fn (int $i) => $clientes[$i % max(count($clientes), 1)] ?? null;

        $pedidos = [
            // ref, origen, estado_final, cliente, [ [sku, cantidad], ... ]
            ['DEMO-1', 'tienda',  'receptado', $cli(0), [['SAL-AJI-250', 2], ['SAL-BBQ-350', 3]]],                 // stock suficiente
            ['DEMO-2', 'cliente', 'receptado', $cli(1), [['LIC-RON-750', 1], ['LIC-AGU-700', 2]]],                 // stock suficiente
            ['DEMO-3', 'erp',     'receptado', $cli(2), [['SAL-MAY-400', 1], ['SRV-ENV', 1000]]],                  // 1 descuenta + 1 bajo pedido → producción
            ['DEMO-4', 'tienda',  'receptado', $cli(0), [['LIC-WHI-750', 50]]],                                    // stock insuficiente → producción
            ['DEMO-5', 'cliente', 'pendiente', $cli(1), [['SAL-HAB-150', 5]]],                                     // abierto, no toca inventario
            ['DEMO-6', 'tienda',  'pagado',    $cli(2), [['CMB-SAL-6', 2]]],                                       // pagado, no receptado
        ];

        foreach ($pedidos as [$ref, $origen, $estadoFinal, $customerId, $lineas]) {
            $this->crearPedido($empresaId, $ref, $origen, $estadoFinal, $customerId, $lineas);
        }
    }

    /**
     * @param array<int, array{0:string, 1:int|float}> $lineas
     */
    private function crearPedido(int $empresaId, string $ref, string $origen, string $estadoFinal, ?int $customerId, array $lineas): void
    {
        // Idempotencia: si ya existe el pedido demo, no repetir (evita re-descuento).
        $existe = StoreOrder::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('referencia_pago', $ref)
            ->exists();
        if ($existe) {
            return;
        }

        DB::transaction(function () use ($empresaId, $ref, $origen, $estadoFinal, $customerId, $lineas) {
            // 1) Cabecera en 'pendiente' (el descuento se dispara al pasar a receptado).
            $order = StoreOrder::withoutGlobalScopes()->create([
                'empresa_id'      => $empresaId,
                'customer_id'     => $customerId,
                'origen'          => $origen,
                'estado'          => 'pendiente',
                'subtotal'        => 0,
                'descuento'       => 0,
                'total'           => 0,
                'estado_pago'     => in_array($estadoFinal, ['pendiente'], true) ? 'pendiente' : 'aprobado',
                'metodo_pago'     => 'transferencia',
                'referencia_pago' => $ref,
                'direccion_envio' => ['calle' => 'Av. Demo 123', 'ciudad' => 'Quito', 'provincia' => 'Pichincha'],
                'notas_cliente'   => "Pedido de prueba {$ref}",
            ]);

            // 2) Líneas (snapshot de nombre/precio del producto).
            $subtotal = 0.0;
            foreach ($lineas as [$sku, $cantidad]) {
                $producto = StoreProduct::withoutGlobalScopes()
                    ->where('empresa_id', $empresaId)
                    ->where('sku', $sku)
                    ->first();
                if (! $producto) {
                    continue;
                }

                $precio    = (float) $producto->precio_venta;
                $importe   = $precio * (float) $cantidad;
                $subtotal += $importe;

                StoreOrderItem::create([
                    'store_order_id'    => $order->id,
                    'store_product_id'  => $producto->id,
                    'inventory_item_id' => $producto->stockItems->first()?->inventory_item_id,
                    'nombre_snapshot'   => $producto->nombre,
                    'precio_unitario'   => $precio,
                    'cantidad'          => $cantidad,
                    'subtotal'          => $importe,
                ]);
            }

            // 3) Totales.
            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            // 4) Estado final. Si es 'receptado', el observer descuenta inventario.
            if ($estadoFinal !== 'pendiente') {
                $order->update(['estado' => $estadoFinal]);
            }
        });
    }
}
