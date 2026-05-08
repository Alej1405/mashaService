<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\Sale;
use App\Services\AccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairAccountingEntriesCommand extends Command
{
    protected $signature = 'accounting:repair-entries
                            {--empresa= : ID de empresa específica}
                            {--dry-run  : Solo reportar, no generar asientos}';

    protected $description = 'Genera asientos contables faltantes en ventas y compras ya confirmadas';

    public function handle(AccountingService $accounting): int
    {
        $dryRun    = $this->option('dry-run');
        $empresaId = $this->option('empresa');

        $this->info($dryRun ? '[DRY-RUN] Solo reportando, sin cambios.' : 'Generando asientos faltantes...');

        $ventasFixed    = $this->repairSales($accounting, $empresaId, $dryRun);
        $comprasFixed   = $this->repairPurchases($accounting, $empresaId, $dryRun);

        $this->newLine();
        $this->info("Resumen: ventas procesadas={$ventasFixed}, compras procesadas={$comprasFixed}");

        return self::SUCCESS;
    }

    private function repairSales(AccountingService $accounting, ?string $empresaId, bool $dryRun): int
    {
        $query = Sale::query()
            ->where('estado', 'confirmado')
            ->whereNull('journal_entry_id')
            ->where('total', '>', 0);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $sales = $query->with('items')->get();

        if ($sales->isEmpty()) {
            $this->line('  Ventas: ninguna pendiente.');
            return 0;
        }

        $fixed = 0;

        foreach ($sales as $sale) {
            $label = "  Venta #{$sale->id} {$sale->referencia} empresa={$sale->empresa_id} total={$sale->total}";

            if ($dryRun) {
                $this->warn("{$label} → pendiente de asiento");
                $fixed++;
                continue;
            }

            try {
                $entry = $accounting->generarAsientoVenta($sale);
                $sale->updateQuietly([
                    'journal_entry_id'   => $entry->id,
                    'error_contable'     => false,
                    'error_contable_msg' => null,
                ]);
                $this->info("{$label} → asiento #{$entry->id} generado ✓");
                $fixed++;
            } catch (\Throwable $e) {
                $this->error("{$label} → ERROR: {$e->getMessage()}");
                $sale->updateQuietly([
                    'error_contable'     => true,
                    'error_contable_msg' => $e->getMessage(),
                ]);
                Log::error('repair-entries: error en venta', [
                    'sale_id' => $sale->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $fixed;
    }

    private function repairPurchases(AccountingService $accounting, ?string $empresaId, bool $dryRun): int
    {
        $query = Purchase::query()
            ->where('status', 'confirmado')
            ->whereNull('journal_entry_id')
            ->where('total', '>', 0);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $purchases = $query->with('items')->get();

        if ($purchases->isEmpty()) {
            $this->line('  Compras: ninguna pendiente.');
            return 0;
        }

        $fixed = 0;

        foreach ($purchases as $purchase) {
            $label = "  Compra #{$purchase->id} {$purchase->number} empresa={$purchase->empresa_id} total={$purchase->total}";

            if ($dryRun) {
                $this->warn("{$label} → pendiente de asiento");
                $fixed++;
                continue;
            }

            try {
                $entry = $accounting->generarAsientoCompra($purchase);
                $purchase->updateQuietly([
                    'journal_entry_id'   => $entry->id,
                    'error_contable'     => false,
                    'error_contable_msg' => null,
                ]);
                $this->info("{$label} → asiento #{$entry->id} generado ✓");
                $fixed++;
            } catch (\Throwable $e) {
                $this->error("{$label} → ERROR: {$e->getMessage()}");
                $purchase->updateQuietly([
                    'error_contable'     => true,
                    'error_contable_msg' => $e->getMessage(),
                ]);
                Log::error('repair-entries: error en compra', [
                    'purchase_id' => $purchase->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return $fixed;
    }
}
