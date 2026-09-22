<?php

namespace App\Observers;

use App\Models\ProductionOrder;
use App\Models\InventoryMovement;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductionOrderObserver
{
    /**
     * Handle the ProductionOrder "updated" event.
     */
    public function updated(ProductionOrder $order): void
    {
        // 1. Detectar cambio a 'completado' y evitar re-ejecución
        if ($order->wasChanged('estado') && $order->estado === 'completado' && is_null($order->journal_entry_id)) {
            
            $order->refresh();
            
            DB::transaction(function () use ($order) {
                try {
                    $order->load(['finishedProduct', 'materials.inventoryItem']);
                    $accountingService = app(AccountingService::class);

                    $kardex = app(\App\Services\KardexService::class);

                    // 2. Consumo de materiales POR EL KARDEX, antes del asiento:
                    //    cada salida se valora al promedio vigente, y ese es el
                    //    costo real de la orden. Hacerlo después obligaría a
                    //    contabilizar un costo y descargar otro.
                    $costoMateriales = 0.0;

                    foreach ($order->materials as $material) {
                        $movimiento = $kardex->registrar([
                            'item'            => $material->inventoryItem,
                            'motivo'          => 'consumo_produccion',
                            'cantidad'        => (float) $material->cantidad_consumida,
                            'fecha'           => $order->fecha,
                            'ubicacion_id'    => $material->inventoryItem->ubicacion_almacen_id,
                            'documento'       => 'Consumo por producción ' . $order->referencia,
                            'referencia_tipo' => 'production_order',
                            'referencia_id'   => $order->id,
                        ]);

                        // El costo de la orden pasa a ser el del kardex, no el
                        // que se estimó al planificarla.
                        $material->updateQuietly([
                            'costo_unitario' => $movimiento->unit_price,
                            'costo_total'    => $movimiento->total,
                        ]);

                        $costoMateriales += (float) $movimiento->total;
                    }

                    $order->updateQuietly(['costo_total' => round($costoMateriales, 2)]);
                    $order->refresh()->load(['finishedProduct', 'materials.inventoryItem']);

                    // 3. Asiento, ya con el costo real: Dr producto terminado /
                    //    Cr materia prima, cuadrado contra el kardex.
                    $journalEntry = $accountingService->generarAsientoProduccion($order);

                    \App\Models\InventoryMovement::where('reference_type', 'production_order')
                        ->where('reference_id', $order->id)
                        ->whereNull('journal_entry_id')
                        ->update(['journal_entry_id' => $journalEntry->id]);

                    // 4. Entrada del producto terminado al costo de producción.
                    $costoUnitarioReal = $order->cantidad_producida > 0
                        ? round($order->costo_total / $order->cantidad_producida, 4)
                        : 0;

                    if ($costoUnitarioReal > 0) {
                        $kardex->registrar([
                            'item'            => $order->finishedProduct,
                            'motivo'          => 'ingreso_produccion',
                            'cantidad'        => (float) $order->cantidad_producida,
                            'costo_unitario'  => $costoUnitarioReal,
                            'fecha'           => $order->fecha,
                            'ubicacion_id'    => $order->finishedProduct->ubicacion_almacen_id,
                            'documento'       => 'Ingreso por producción ' . $order->referencia,
                            'referencia_tipo' => 'production_order',
                            'referencia_id'   => $order->id,
                            'asiento_id'      => $journalEntry->id,
                        ]);
                    }

                    // purchase_price ya no se pisa: el costo de producción vive
                    // en costo_promedio, y el precio de compra es otra cosa.

                    // 5. Cierre de la Orden (Silencioso para evitar recursión)
                    $order->updateQuietly([
                        'journal_entry_id' => $journalEntry->id,
                        'completado_por'   => auth()->id(),
                    ]);

                } catch (\Exception $e) {
                    Log::error("Error en ProductionOrderObserver: " . $e->getMessage());
                    throw $e;
                }
            });
        }
    }
}
