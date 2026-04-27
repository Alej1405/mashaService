<?php

namespace App\Observers;

use App\Models\InventoryMovement;

class InventoryMovementObserver
{
    /**
     * Handle the InventoryMovement "created" event.
     */
    public function created(InventoryMovement $inventoryMovement): void
    {
        // Compras y producción tienen su propio Observer que genera el asiento
        if (in_array($inventoryMovement->reference_type, ['purchase', 'purchase_void', 'production_order', 'manufacture'])) {
            return;
        }

        if ($inventoryMovement->reference_type === 'adjustment' || $inventoryMovement->reference_type === 'ajuste') {
            \App\Services\AccountingService::generarAsientoAjuste($inventoryMovement);
        }
    }

    /**
     * Handle the InventoryMovement "updated" event.
     */
    public function updated(InventoryMovement $inventoryMovement): void
    {
        //
    }

    /**
     * Handle the InventoryMovement "deleted" event.
     */
    public function deleted(InventoryMovement $inventoryMovement): void
    {
        //
    }

    /**
     * Handle the InventoryMovement "restored" event.
     */
    public function restored(InventoryMovement $inventoryMovement): void
    {
        //
    }

    /**
     * Handle the InventoryMovement "force deleted" event.
     */
    public function forceDeleted(InventoryMovement $inventoryMovement): void
    {
        //
    }
}
