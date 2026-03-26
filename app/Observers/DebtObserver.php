<?php

namespace App\Observers;

use App\Models\Debt;
use App\Services\AccountingService;
use App\Services\DebtService;
use Illuminate\Support\Facades\Log;

class DebtObserver
{
    public function updated(Debt $debt): void
    {
        // Generar asiento contable al activar por primera vez
        if ($debt->wasChanged('estado') && $debt->estado === 'activa' && !$debt->journal_entry_id) {
            try {
                $accountingService = new AccountingService();
                $entry = $accountingService->generarAsientoDeuda($debt);
                $debt->updateQuietly([
                    'journal_entry_id' => $entry->id,
                    'saldo_pendiente'  => $debt->monto_original,
                ]);

                // Generar tabla de amortización si tiene cuotas definidas
                if ($debt->numero_cuotas && $debt->numero_cuotas > 0) {
                    $debtService = new DebtService();
                    $debtService->generarLineasAmortizacion($debt);
                }
            } catch (\Exception $e) {
                Log::error("Error al generar asiento de deuda {$debt->id}: " . $e->getMessage());
                throw $e;
            }
        }
    }
}
