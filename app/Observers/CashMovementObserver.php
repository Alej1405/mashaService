<?php

namespace App\Observers;

use App\Models\CashMovement;

class CashMovementObserver
{
    public function created(\App\Models\CashMovement $cashMovement): void
    {
        // Solo generar asiento si es un movimiento manual (sin referencia)
        // O si explicitly lo requiere el negocio (egresos varios, depósitos)
        // Por ahora, si es automático (origen compra/venta), ya tiene el journal_entry_id de la cabecera
        if ($cashMovement->journal_entry_id) {
            return;
        }

        // Lógica para movimientos manuales podría ir aquí
    }

    /**
     * Handle the CashMovement "updated" event.
     */
    public function updated(CashMovement $cashMovement): void
    {
        //
    }

    /**
     * Handle the CashMovement "deleted" event.
     */
    public function deleted(CashMovement $cashMovement): void
    {
        //
    }

    /**
     * Handle the CashMovement "restored" event.
     */
    public function restored(CashMovement $cashMovement): void
    {
        //
    }

    /**
     * Handle the CashMovement "force deleted" event.
     */
    public function forceDeleted(CashMovement $cashMovement): void
    {
        //
    }
}
