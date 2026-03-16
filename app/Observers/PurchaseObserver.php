<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\InventoryMovement;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class PurchaseObserver
{
    public function updated(Purchase $purchase): void
    {
        if (!$purchase->isDirty('status') || 
            $purchase->status !== 'confirmado') {
            return;
        }

        if ($purchase->journal_entry_id) {
            return;
        }

        try {
            $journalEntry = app(AccountingService::class)
                ->generarAsientoCompra($purchase);
            
            $purchase->updateQuietly([
                'journal_entry_id' => $journalEntry->id,
                'confirmado_por'   => auth()->id(),
                'confirmado_at'    => now(),
            ]);

            // --- Lógica de Tesorería ---
            if ($purchase->forma_pago === 'efectivo' && $purchase->cash_register_id) {
                $sesion = \App\Models\CashSession::where('cash_register_id', $purchase->cash_register_id)
                    ->where('estado', 'abierta')
                    ->latest()->first();

                \App\Models\CashMovement::create([
                    'empresa_id'       => $purchase->empresa_id,
                    'cash_register_id' => $purchase->cash_register_id,
                    'cash_session_id'  => $sesion?->id,
                    'tipo'             => 'egreso',
                    'origen'           => 'compra',
                    'referencia_tipo'  => 'purchase',
                    'referencia_id'    => $purchase->id,
                    'journal_entry_id' => $journalEntry->id,
                    'monto'            => $purchase->total,
                    'descripcion'      => 'Compra ' . $purchase->number,
                    'fecha'            => $purchase->date,
                ]);
            }

            if (($purchase->forma_pago === 'tarjeta' || $purchase->forma_pago === 'tarjeta_credito') && $purchase->credit_card_id) {
                \App\Models\CreditCardMovement::create([
                    'empresa_id'       => $purchase->empresa_id,
                    'credit_card_id'   => $purchase->credit_card_id,
                    'tipo'             => 'cargo',
                    'referencia_tipo'  => 'purchase',
                    'referencia_id'    => $purchase->id,
                    'journal_entry_id' => $journalEntry->id,
                    'monto'            => $purchase->total,
                    'descripcion'      => 'Compra ' . $purchase->number,
                    'fecha'            => $purchase->date,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error generando asiento de compra', [
                'purchase_id' => $purchase->id,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
