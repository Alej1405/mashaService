<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detecta y anula ventas duplicadas generadas por el bug del LogisticsShipmentObserver,
 * y reporta clientes ERP duplicados por empresa + número de identificación.
 *
 * Uso:
 *   php artisan logistics:limpiar-duplicados              (solo reporte, no modifica nada)
 *   php artisan logistics:limpiar-duplicados --fix        (anula las ventas duplicadas)
 *   php artisan logistics:limpiar-duplicados --empresa=5  (filtra por empresa)
 */
class LogisticsLimpiarDuplicados extends Command
{
    protected $signature = 'logistics:limpiar-duplicados
                            {--fix : Anula las ventas duplicadas (sin esta flag solo reporta)}
                            {--empresa= : ID de empresa a analizar (por defecto todas)}';

    protected $description = 'Detecta ventas duplicadas de embarques y clientes ERP duplicados';

    public function handle(): int
    {
        $empresaId = $this->option('empresa') ? (int) $this->option('empresa') : null;
        $fix       = $this->option('fix');

        $this->info('');
        $this->info('══════════════════════════════════════════════════════════');
        $this->info('  DIAGNÓSTICO DE DUPLICADOS LOGÍSTICOS');
        $this->info('  Modo: ' . ($fix ? '⚠️  FIX (anulará duplicados)' : '🔍 Solo lectura (dry-run)'));
        if ($empresaId) {
            $this->info("  Empresa filtrada: #{$empresaId}");
        }
        $this->info('══════════════════════════════════════════════════════════');
        $this->info('');

        $this->reportarVentasDuplicadas($empresaId, $fix);
        $this->info('');
        $this->reportarClientesDuplicados($empresaId);

        if (! $fix) {
            $this->info('');
            $this->comment('Para aplicar los fixes ejecuta con --fix:');
            $this->comment('  php artisan logistics:limpiar-duplicados --fix');
        }

        return self::SUCCESS;
    }

    // ── Ventas duplicadas ────────────────────────────────────────────────────

    private function reportarVentasDuplicadas(?int $empresaId, bool $fix): void
    {
        $this->info('─── VENTAS DUPLICADAS DE EMBARQUES ───');

        // Busca ventas generadas automáticamente desde embarque (contienen "EMB-" en notas)
        // que tengan el mismo (empresa_id, customer_id, fecha, notas) → duplicadas
        $query = Sale::query()
            ->where('tipo_venta', 'servicio')
            ->where('notas', 'like', '%Generada automáticamente desde embarque EMB-%')
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('empresa_id')
            ->orderBy('customer_id')
            ->orderBy('notas')
            ->orderBy('id');

        $ventas  = $query->get();
        $grupos  = $ventas->groupBy(fn ($s) => $s->empresa_id . '_' . $s->customer_id . '_' . $this->extractarEmbarque($s->notas ?? ''));

        $totalDups = 0;

        foreach ($grupos as $clave => $grupo) {
            if ($grupo->count() < 2) {
                continue;
            }

            $original    = $grupo->first();
            $duplicadas  = $grupo->slice(1);
            $totalDups  += $duplicadas->count();

            $this->line('');
            $this->warn("  Grupo: {$clave}");
            $this->line("  ✅ ORIGINAL  → Sale #{$original->id} | Estado: {$original->estado} | Total: \${$original->total} | {$original->created_at}");

            foreach ($duplicadas as $dup) {
                $items = SaleItem::where('sale_id', $dup->id)->count();
                $this->error("  ❌ DUPLICADA → Sale #{$dup->id} | Estado: {$dup->estado} | Total: \${$dup->total} | Items: {$items} | {$dup->created_at}");

                if ($fix) {
                    $this->anularVenta($dup);
                    $this->line("     → Anulada.");
                }
            }
        }

        if ($totalDups === 0) {
            $this->info('  ✅ No se encontraron ventas duplicadas de embarques.');
        } else {
            $accion = $fix ? 'anuladas' : 'encontradas';
            $this->info('');
            $this->info("  Total duplicadas {$accion}: {$totalDups}");
        }
    }

    private function anularVenta(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            // Anular en lugar de eliminar: mantiene trazabilidad y no rompe FK de asientos
            $sale->update([
                'estado' => 'anulado',
                'notas'  => ($sale->notas ?? '') . ' [ANULADA: duplicado por bug Observer – ' . now()->toDateTimeString() . ']',
            ]);
        });
    }

    private function extractarEmbarque(string $notas): string
    {
        if (preg_match('/EMB-\d{4}-\d+/', $notas, $m)) {
            return $m[0];
        }
        return $notas;
    }

    // ── Clientes duplicados ──────────────────────────────────────────────────

    private function reportarClientesDuplicados(?int $empresaId): void
    {
        $this->info('─── CLIENTES ERP DUPLICADOS (mismo empresa_id + numero_identificacion) ───');

        $query = DB::table('customers')
            ->select('empresa_id', 'numero_identificacion', DB::raw('COUNT(*) as total'), DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->groupBy('empresa_id', 'numero_identificacion')
            ->having('total', '>', 1)
            ->orderByDesc('total');

        $duplicados = $query->get();

        if ($duplicados->isEmpty()) {
            $this->info('  ✅ No se encontraron clientes ERP duplicados.');
            return;
        }

        $this->line('');
        $this->warn('  ⚠️  Se encontraron clientes duplicados. Revisión manual recomendada.');
        $this->line('');

        $headers = ['Empresa', 'Identificación', 'IDs duplicados', 'Cantidad'];
        $rows    = $duplicados->map(fn ($r) => [
            $r->empresa_id,
            $r->numero_identificacion,
            $r->ids,
            $r->total,
        ])->toArray();

        $this->table($headers, $rows);

        $this->info('');
        $this->comment('  Para fusionar, decide qué ID conservar y reasigna las FK:');
        $this->comment('  UPDATE sales SET customer_id=<id_bueno> WHERE customer_id=<id_duplicado>;');
        $this->comment('  UPDATE store_customers SET customer_id=<id_bueno> WHERE customer_id=<id_duplicado>;');
        $this->comment('  UPDATE logistics_payment_claims SET customer_id=<id_bueno> WHERE customer_id=<id_duplicado>;');
        $this->comment('  DELETE FROM customers WHERE id=<id_duplicado>;');
    }
}
