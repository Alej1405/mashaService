<?php

namespace App\Observers;

use App\Models\DebtPayment;
use App\Services\AccountingService;
use App\Services\DebtService;
use Illuminate\Support\Facades\Log;

class DebtPaymentObserver
{
    public function created(DebtPayment $payment): void
    {
        try {
            // 1. Generar asiento contable
            $accountingService = new AccountingService();
            $entry = $accountingService->generarAsientoPagoDeuda($payment);
            $payment->updateQuietly(['journal_entry_id' => $entry->id]);

            // 2. Marcar línea de amortización como pagada si corresponde
            if ($payment->debt_amortization_line_id) {
                $payment->amortizationLine?->update(['estado' => 'pagada']);
            } elseif ($payment->numero_cuota) {
                $payment->debt->amortizationLines()
                    ->where('numero_cuota', $payment->numero_cuota)
                    ->where('estado', '!=', 'pagada')
                    ->first()
                    ?->update(['estado' => 'pagada']);
            }

            // 3. Recalcular saldo y estado de la deuda
            $debtService = new DebtService();
            $debtService->actualizarSaldoYEstado($payment->debt);

        } catch (\Exception $e) {
            Log::error("Error al procesar pago de deuda {$payment->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
