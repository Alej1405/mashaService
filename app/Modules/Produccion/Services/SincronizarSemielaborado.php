<?php

namespace App\Modules\Produccion\Services;

use App\Models\InventoryItem;
use App\Models\ProductDesign;
use App\Models\ProductPresentation;

/**
 * Un semielaborado (materia prima procesada) se crea como ítem de inventario. Por
 * detrás necesita una presentación con su receta, porque de ahí saca su costo el
 * microservicio de formulación. Este servicio arma/actualiza ese «aparato» oculto:
 * el usuario solo ve el ítem y su receta, nunca el diseño ni la presentación.
 */
class SincronizarSemielaborado
{
    /**
     * @param  array<int,array<string,mixed>>  $receta  líneas: inventory_item_id, cantidad, measurement_unit_id
     */
    public function sincronizar(InventoryItem $item, array $receta): void
    {
        // Diseño oculto del semielaborado (uno por ítem).
        $design = ProductDesign::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $item->empresa_id, 'inventory_item_id' => $item->id],
            [
                'nombre'          => $item->nombre,
                'es_semielaborado' => true,
                'unidad_base_id'  => $item->measurement_unit_id,
                'cantidad_base'   => 1,
                'activo'          => true,
            ],
        );

        // La presentación trae el inventory_item_id del ítem para que su observer
        // (PresentacionComoProducto) reuse ESTE ítem y no cree otro; y por ser
        // semielaborado tampoco genera producto de tienda.
        $presentacion = ProductPresentation::withoutGlobalScopes()->firstOrCreate(
            ['product_design_id' => $design->id],
            [
                'nombre'                     => $item->nombre,
                'inventory_item_id'          => $item->id,
                'measurement_unit_id'        => $item->measurement_unit_id,
                'cantidad_minima_produccion' => 1,
                'activa'                     => true,
            ],
        );

        // El ítem apunta a su presentación: así el microservicio sabe con qué receta costearlo.
        if ((int) $item->product_presentation_id !== (int) $presentacion->id) {
            $item->updateQuietly(['product_presentation_id' => $presentacion->id]);
        }

        // La receta vive en la presentación (de ahí la lee el microservicio). Se reemplaza entera.
        $presentacion->formulaLines()->delete();

        foreach ($receta as $linea) {
            if (empty($linea['inventory_item_id']) || empty($linea['cantidad'])) {
                continue;
            }

            $presentacion->formulaLines()->create([
                'inventory_item_id'   => $linea['inventory_item_id'],
                'cantidad'            => $linea['cantidad'],
                'measurement_unit_id' => $linea['measurement_unit_id'] ?? null,
            ]);
        }
    }
}
