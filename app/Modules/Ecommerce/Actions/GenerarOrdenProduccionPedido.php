<?php

namespace App\Modules\Ecommerce\Actions;

use App\Models\ProductionOrder;
use App\Models\StoreOrder;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Se fabrica bajo pedido: el pedido genera UNA orden de producción (PROD-AAAA-#####)
 * en borrador, y cada producto pedido es una línea de esa orden con su cantidad.
 *
 * Idempotente: una línea del pedido no se agrega dos veces. Un producto sin
 * presentación (sin diseño) no se puede producir y se informa.
 */
#[Documentado(
    grupo: 'Tienda',
    descripcion: 'Genera la orden de producción de un pedido, con una línea por producto pedido.',
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

        $lineas = [];
        $sinDiseno = [];

        foreach ($order->orderItems as $item) {
            if ($nombres !== null && ! in_array($item->product?->nombre, $nombres, true)) {
                continue;
            }

            $presentacion = $item->product?->productPresentation;
            if (! $presentacion) {
                $sinDiseno[] = $item->nombre_snapshot;
                continue;
            }

            $lineas[] = [$item, $presentacion];
        }

        if (! $lineas) {
            return ['ordenes' => collect(), 'sin_diseno' => $sinDiseno];
        }

        $orden = DB::transaction(function () use ($order, $lineas) {
            $orden = ProductionOrder::withoutGlobalScopes()->firstOrCreate(
                ['store_order_id' => $order->id],
                [
                    'empresa_id'         => $order->empresa_id,
                    'fecha'              => now()->toDateString(),
                    'cantidad_producida' => 0,
                    'costo_total'        => 0,
                    'estado'             => 'borrador',
                    'notas'              => "Pedido {$order->numero} · " . ($order->customer?->nombre_completo ?? 'Cliente'),
                ],
            );

            foreach ($lineas as [$item, $presentacion]) {
                $orden->lineas()->firstOrCreate(['store_order_item_id' => $item->id], [
                    'product_presentation_id' => $presentacion->id,
                    'inventory_item_id'       => $presentacion->inventory_item_id ?: $item->inventory_item_id,
                    'descripcion'             => $presentacion->nombre ?: $item->nombre_snapshot,
                    'cantidad'                => (float) $item->cantidad,
                ]);
            }

            // Unidades totales de la orden: la suma de sus líneas.
            $orden->update(['cantidad_producida' => $orden->lineas()->sum('cantidad')]);

            return $orden;
        });

        return ['ordenes' => collect([$orden]), 'sin_diseno' => $sinDiseno];
    }
}
