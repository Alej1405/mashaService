<?php

namespace App\Observers;

use App\Models\LogisticsShipmentBill;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class LogisticsShipmentBillObserver
{
    public function updated(LogisticsShipmentBill $bill): void
    {
        if (! $bill->wasChanged('estado')) {
            return;
        }

        if ($bill->estado !== 'pagada') {
            return;
        }

        if ($bill->journal_entry_id) {
            return; // ya tiene asiento, evitar duplicados
        }

        try {
            $entry = app(AccountingService::class)->generarAsientoEgresoLogistico($bill);
            $bill->updateQuietly(['journal_entry_id' => $entry->id]);
        } catch (\Throwable $e) {
            Log::error('LogisticsShipmentBillObserver: error generando asiento de egreso', [
                'bill_id' => $bill->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
