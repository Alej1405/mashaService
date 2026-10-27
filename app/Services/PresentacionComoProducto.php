<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\ProductPresentation;
use App\Models\StoreProduct;
use App\Models\StoreProductStock;

/**
 * Una presentación es lo que se vende: tiene su propio ítem de inventario (stock,
 * kardex y costo aparte de las otras presentaciones) y su producto de tienda.
 *
 * El nombre del ítem es el de la presentación, que es el nombre con que se factura,
 * y su código es el del SRI. El producto de tienda nace sin publicar y después es
 * de marketing: aquí no se le vuelve a tocar nada.
 */
class PresentacionComoProducto
{
    public function sincronizar(ProductPresentation $p): void
    {
        $diseno = $p->productDesign()->withoutGlobalScopes()->first();

        if (! $diseno || blank($p->nombre)) {
            return;
        }

        $item = $this->item($p, $diseno->empresa_id);

        if (! $p->storeProduct()->withoutGlobalScopes()->exists()) {
            $producto = StoreProduct::withoutGlobalScopes()->create([
                'empresa_id'              => $diseno->empresa_id,
                'product_presentation_id' => $p->id,
                'store_category_id'       => $diseno->store_category_id,
                'nombre'                  => $p->nombre,
                'sku'                     => $p->codigo_sri,
                'precio_venta'            => (float) $p->pvp_estimado,
                'publicado'               => false,
            ]);

            // El stock de la tienda es el del ítem de la presentación.
            StoreProductStock::withoutGlobalScopes()->create([
                'empresa_id' => $diseno->empresa_id, 'store_product_id' => $producto->id,
                'inventory_item_id' => $item->id, 'cantidad' => 1,
            ]);
        }
    }

    private function item(ProductPresentation $p, int $empresaId): InventoryItem
    {
        $datos = array_filter([
            'nombre' => $p->nombre,
            'codigo' => $p->codigo_sri ?: null,
        ]);

        // El ítem de la presentación, o el que ya tenía ese código del SRI en el inventario.
        $item = $p->inventory_item_id
            ? InventoryItem::withoutGlobalScopes()->find($p->inventory_item_id)
            : ($p->codigo_sri ? InventoryItem::withoutGlobalScopes()->where('empresa_id', $empresaId)
                ->where('codigo', $p->codigo_sri)->whereNull('product_presentation_id')->first() : null);

        if ($item) {
            $item->fill($datos + ['product_presentation_id' => $p->id])->save();
            if (! $p->inventory_item_id) {
                $p->updateQuietly(['inventory_item_id' => $item->id]);
            }

            return $item;
        }

        $item = InventoryItem::withoutGlobalScopes()->create($datos + [
            'empresa_id'              => $empresaId,
            'type'                    => 'producto_terminado',
            'product_presentation_id' => $p->id,
            'sale_price'              => (float) $p->pvp_estimado,
            'activo'                  => true,
            'stock_actual'            => 0,
        ]);
        $p->updateQuietly(['inventory_item_id' => $item->id]);

        return $item;
    }
}
