<?php

namespace App\Modules\Ecommerce\Actions;

use App\Models\ProductionOrder;
use App\Models\StoreOrder;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Collection;

/**
 * Se fabrica bajo pedido: cada línea del pedido genera su orden de producción
 * (PROD-AAAA-#####) en borrador, ligada al pedido, por la presentación y la
 * cantidad pedidas. Los materiales y el consumo se definen en Producción.
 *
 * Idempotente: si la línea ya tiene su orden, no se crea otra. Una línea cuyo
 * producto no tiene presentación (sin diseño) no se puede producir y se informa.
 */
#[Documentado(
    grupo: 'Tienda',
    descripcion: 'Genera las órdenes de producción de un pedido, una por línea, en borrador y ligadas al pedido.',
    tipo: 'action',
)]
final class GenerarOrdenProduccionPedido
{
    /**
     * @param array<int, array{producto:string}>|null $soloEstos nombres de producto (recepción); null = todo el pedido
     * @return array{ordenes: Collection<int,ProductionOrder>, sin_diseno: array<int,string>}
     */
    public function handle(StoreOrder $order, ?array $soloEstos = null): array
    {
        $order->loadMissing('customer', 'orderItems.product.productPresentation');
        $nombres = $soloEstos === null ? null : array_column($soloEstos, 'producto');

        $ordenes = collect();
        $sinDiseno = [];

        foreach ($order->orderItems as $linea) {
            $presentacion = $linea->product?->productPresentation;

            if ($nombres !== null && ! in_array($linea->product?->nombre, $nombres, true)) {
                continue;
            }

            if (! $presentacion) {
                $sinDiseno[] = $linea->nombre_snapshot;
                continue;
            }

            $ordenes->push(ProductionOrder::withoutGlobalScopes()->firstOrCreate(
                ['store_order_id' => $order->id, 'product_presentation_id' => $presentacion->id],
                [
                    'empresa_id'         => $order->empresa_id,
                    'fecha'              => now()->toDateString(),
                    'inventory_item_id'  => $presentacion->inventory_item_id ?: $linea->inventory_item_id,
                    'cantidad_producida' => (float) $linea->cantidad,
                    'costo_total'        => 0,
                    'estado'             => 'borrador',
                    'notas'              => "Pedido {$order->numero} · " . ($order->customer?->nombre_completo ?? 'Cliente'),
                ],
            ));
        }

        return ['ordenes' => $ordenes, 'sin_diseno' => $sinDiseno];
    }
}
