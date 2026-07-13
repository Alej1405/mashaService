<?php

namespace App\Modules\Ecommerce\Actions;

use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\StoreProduct;
use App\Models\StoreProductStock;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\DB;

/**
 * Cuando un producto se crea DESDE la tienda, el operador no elige a mano un item de
 * inventario (podría enlazar cosas sin relación). En su lugar, aquí se crea
 * automáticamente su item de inventario de PRODUCTO TERMINADO y se vincula 1:1.
 *
 * - Las "existencias iniciales" capturadas en la tienda quedan como stock_actual del
 *   item. A partir de ahí el stock SOLO se edita desde el ERP (módulo Inventario);
 *   las ventas lo descuentan vía RecepcionarPedido.
 * - El vínculo N:M avanzado (producto respaldado por varios items) se hace desde el
 *   ERP, no desde la tienda.
 *
 * Idempotente: si el producto ya tiene origen de stock, no hace nada. No genera
 * asientos contables (el alta de un item no es un movimiento).
 */
#[Documentado(
    grupo: 'Tienda',
    descripcion: 'Al crear un producto desde la tienda, genera automáticamente su item de inventario (producto terminado) con las existencias iniciales y lo vincula 1:1. El stock luego se gestiona desde el ERP.',
    tipo: 'action',
)]
final class AsegurarInventarioProducto
{
    public function handle(StoreProduct $producto, float $existenciasIniciales = 0): InventoryItem
    {
        return DB::transaction(function () use ($producto, $existenciasIniciales) {
            // Idempotencia: si ya hay origen de stock, devolvemos el primero y no duplicamos.
            $existente = $producto->stockItems()->with('inventoryItem')->first();
            if ($existente?->inventoryItem) {
                return $existente->inventoryItem;
            }

            // Si la unidad de precio coincide con una del catálogo, la heredamos al item.
            $unidadId = MeasurementUnit::withoutGlobalScopes()
                ->where('empresa_id', $producto->empresa_id)
                ->where('nombre', $producto->unidad_precio)
                ->value('id');

            $item = InventoryItem::create([
                'empresa_id'          => $producto->empresa_id,
                'nombre'              => $producto->nombre,
                'type'                => 'producto_terminado',
                'measurement_unit_id' => $unidadId,
                'conversion_factor'   => 1,
                'purchase_price'      => 0,
                'sale_price'          => $producto->precio_venta,
                'stock_actual'        => max($existenciasIniciales, 0),
                'stock_minimo'        => 0,
                'activo'              => true,
            ]);

            StoreProductStock::create([
                'empresa_id'        => $producto->empresa_id,
                'store_product_id'  => $producto->id,
                'inventory_item_id' => $item->id,
                'cantidad'          => 1,
            ]);

            return $item;
        });
    }
}
