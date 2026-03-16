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
        if ($order->isDirty('estado') && $order->estado === 'completado' && is_null($order->journal_entry_id)) {
            
            $order->refresh();
            
            DB::transaction(function () use ($order) {
                try {
                    $order->load(['finishedProduct', 'materials.inventoryItem']);
                    $accountingService = app(AccountingService::class);

                    // 2. Generar Asiento Contable
                    $journalEntry = $accountingService->generarAsientoProduccion($order);

                    // 3. Movimientos de Salida (Consumo de Materiales)
                    foreach ($order->materials as $material) {
                        InventoryMovement::create([
                            'empresa_id'        => $order->empresa_id,
                            'inventory_item_id' => $material->inventory_item_id,
                            'type'              => 'salida',
                            'quantity'          => -$material->cantidad_consumida,
                            'unit_price'        => $material->costo_unitario,
                            'total'             => $material->costo_total,
                            'reference_type'    => 'production_order',
                            'reference_id'      => $order->id,
                            'journal_entry_id'  => $journalEntry->id,
                            'notes'             => 'Consumo por producción ' . $order->referencia,
                            'date'              => $order->fecha,
                        ]);

                        // Descontar stock del catálogo
                        $material->inventoryItem->decrement('stock_actual', $material->cantidad_consumida);
                    }

                    // 4. Movimiento de Entrada (Producto Terminado)
                    $costoUnitarioReal = $order->cantidad_producida > 0 
                        ? $order->costo_total / $order->cantidad_producida 
                        : 0;

                    InventoryMovement::create([
                        'empresa_id'        => $order->empresa_id,
                        'inventory_item_id' => $order->inventory_item_id,
                        'type'              => 'entrada',
                        'quantity'          => $order->cantidad_producida,
                        'unit_price'        => $costoUnitarioReal,
                        'total'             => $order->costo_total,
                        'reference_type'    => 'production_order',
                        'reference_id'      => $order->id,
                        'journal_entry_id'  => $journalEntry->id,
                        'notes'             => 'Ingreso por producción ' . $order->referencia,
                        'date'              => $order->fecha,
                    ]);

                    // Incrementar stock y actualizar costo en el catálogo
                    $order->finishedProduct->increment('stock_actual', $order->cantidad_producida);
                    $order->finishedProduct->update([
                        'purchase_price' => $costoUnitarioReal // Asumimos purchase_price como costo base para inventario
                    ]);

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
