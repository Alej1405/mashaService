<?php

namespace App\Modules\Produccion\Services;

use App\Models\InventoryItem;
use App\Models\ProductPresentation;
use App\Models\StoreOrder;
use Illuminate\Support\Collection;

/**
 * El embudo: junta la demanda de materias primas procesadas de varios pedidos y
 * dice cuánto hay que PREPARAR de cada una antes de ensamblar.
 *
 * El stock manda: si hay suficiente, no se prepara; si falta, se prepara el faltante.
 * Un semielaborado marcado «solo bajo pedido» se prepara fresco por toda la demanda,
 * sin contar stock. El costo y los asientos los hace la orden de producción al
 * cerrarse (ProductionOrderObserver); aquí solo se calcula qué y cuánto.
 *
 * El escalado de receta comparte lógica con ProduccionPage::cantNecesariaStock; si se
 * toca uno, revisar el otro.
 */
class ConsolidacionSemielaborados
{
    /**
     * @param  Collection<int,StoreOrder>  $pedidos  pedidos a producir en la corrida
     * @return array<int,array<string,mixed>>  un bloque por semielaborado
     */
    public function paraPedidos(Collection $pedidos): array
    {
        // 1) Sumar la demanda de cada semielaborado en todos los productos del pedido.
        $demanda = []; // item_id => cantidad (en la unidad del ítem)

        foreach ($pedidos as $pedido) {
            $pedido->loadMissing('orderItems.product.productPresentation.formulaLines.inventoryItem');

            foreach ($pedido->orderItems as $item) {
                $pres = $item->product?->productPresentation;
                if (! $pres) {
                    continue;
                }

                $lote      = max((float) $pres->cantidad_minima_produccion, 0.0001);
                $cantidad  = (float) $item->cantidad;

                foreach ($pres->formulaLines as $line) {
                    $insumo = $line->inventoryItem;
                    if (! $insumo || $insumo->type !== 'materia_prima_procesada') {
                        continue;
                    }
                    $demanda[$insumo->id] = ($demanda[$insumo->id] ?? 0) + $this->escalar($line, $cantidad, $lote, $insumo);
                }
            }
        }

        // 2) Por cada semielaborado: cuánto preparar (stock / bajo pedido) y qué consume.
        $resultado = [];

        foreach ($demanda as $itemId => $total) {
            $item = InventoryItem::withoutGlobalScopes()->find($itemId);
            if (! $item) {
                continue;
            }

            $pres = $item->product_presentation_id
                ? ProductPresentation::withoutGlobalScopes()
                    ->with('productDesign', 'formulaLines.inventoryItem', 'formulaLines.measurementUnit', 'measurementUnit')
                    ->find($item->product_presentation_id)
                : null;

            $bajoPedido = (bool) $item->solo_bajo_pedido;
            $stock      = max(0.0, (float) $item->stock_actual);

            // El stock manda: si no es bajo pedido, solo el faltante.
            $aPreparar = $bajoPedido ? $total : max(0.0, $total - $stock);

            // Se respeta el lote mínimo del semielaborado.
            $loteMin = (float) ($pres?->cantidad_minima_produccion ?? 0);
            if ($aPreparar > 0 && $loteMin > 0 && $aPreparar < $loteMin) {
                $aPreparar = $loteMin;
            }

            $resultado[] = [
                'item_id'         => $item->id,
                'presentacion_id' => $pres?->id,
                'nombre'          => $item->nombre,
                'unidad'          => $item->measurementUnit?->abreviatura ?? '',
                'demanda'         => round($total, 4),
                'stock'           => round($stock, 4),
                'bajo_pedido'     => $bajoPedido,
                'a_preparar'      => round($aPreparar, 4),
                'consume'         => $aPreparar > 0 ? $this->consumoReceta($pres, $aPreparar) : [],
            ];
        }

        return $resultado;
    }

    /** Lo que la receta del semielaborado consume para preparar $cantidad. */
    private function consumoReceta(?ProductPresentation $pres, float $cantidad): array
    {
        if (! $pres || $pres->formulaLines->isEmpty()) {
            return [];
        }

        $lote = max((float) $pres->cantidad_minima_produccion, 0.0001);

        return $pres->formulaLines
            ->filter(fn ($l) => $l->inventoryItem)
            ->map(fn ($l) => [
                'nombre'   => $l->inventoryItem->nombre,
                'cantidad' => round($this->escalar($l, $cantidad, $lote, $l->inventoryItem), 4),
                'unidad'   => $l->measurementUnit?->abreviatura ?? ($l->inventoryItem->measurementUnit?->abreviatura ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * Cantidad de un insumo para producir $cantUnidades, escalando la receta y
     * convirtiendo a la unidad del ítem.
     */
    private function escalar($line, float $cantUnidades, float $lote, InventoryItem $item): float
    {
        $need = ((float) $line->cantidad / $lote) * $cantUnidades;

        if (! $line->measurement_unit_id || $line->measurement_unit_id === $item->measurement_unit_id) {
            return $need;
        }
        if ($item->purchase_unit_id && $line->measurement_unit_id === $item->purchase_unit_id) {
            return $need * max((float) $item->conversion_factor, 0.000001);
        }

        return $need;
    }
}
