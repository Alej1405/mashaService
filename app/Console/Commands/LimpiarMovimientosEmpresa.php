<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deja una empresa sin movimientos, para empezar a registrar de verdad.
 *
 * Existe porque un cliente nuevo estrena el ERP con datos de prueba dentro: las
 * compras con las que alguien aprendió a usarlo, sus asientos y su kardex. Eso
 * no se corrige mes a mes, se borra antes de arrancar.
 *
 * **Borra**: compras con sus líneas, asientos con sus líneas, movimientos de
 * kardex, y pone el stock y el costo de todos los ítems en cero.
 *
 * **No borra**: el catálogo (ítems, proveedores, clientes, plan de cuentas), ni
 * las deudas, sus pagos y los movimientos de caja, que no son compras: a esos
 * solo se les quita el asiento que se va.
 *
 *   php artisan erp:limpiar-movimientos --empresa=1 --hasta=2026-09-01 --dry-run
 *   php artisan erp:limpiar-movimientos --empresa=1
 */
class LimpiarMovimientosEmpresa extends Command
{
    protected $signature = 'erp:limpiar-movimientos
        {--empresa= : Id de la empresa}
        {--hasta= : Solo lo anterior a esta fecha (por defecto, todo)}
        {--dry-run : Enseña lo que borraría y no borra nada}';

    protected $description = 'Borra compras, asientos y kardex de una empresa, con respaldo previo';

    public function handle(): int
    {
        $empresaId = (int) $this->option('empresa');

        if (! $empresaId) {
            $this->error('Falta --empresa=<id>.');

            return self::FAILURE;
        }

        $empresa = DB::table('empresas')->find($empresaId);

        if (! $empresa) {
            $this->error("No existe la empresa {$empresaId}.");

            return self::FAILURE;
        }

        $hasta = $this->option('hasta');
        $seco  = (bool) $this->option('dry-run');

        $compras = DB::table('purchases')->where('empresa_id', $empresaId)
            ->when($hasta, fn ($q) => $q->where('date', '<', $hasta))->pluck('id');

        $asientos = DB::table('journal_entries')->where('empresa_id', $empresaId)
            ->when($hasta, fn ($q) => $q->where('fecha', '<', $hasta))->pluck('id');

        $kardex = DB::table('inventory_movements')->where('empresa_id', $empresaId)
            ->when($hasta, fn ($q) => $q->where('date', '<', $hasta))->pluck('id');

        $this->info("Empresa: {$empresa->name}" . ($hasta ? " · solo lo anterior a {$hasta}" : ' · todo'));
        $this->table(['Qué', 'Cuántos'], [
            ['compras', $compras->count()],
            ['líneas de compra', DB::table('purchase_items')->whereIn('purchase_id', $compras)->count()],
            ['asientos', $asientos->count()],
            ['líneas de asiento', DB::table('journal_entry_lines')->whereIn('journal_entry_id', $asientos)->count()],
            ['movimientos de kardex', $kardex->count()],
            ['ítems que quedan en cero', DB::table('inventory_items')->where('empresa_id', $empresaId)->count()],
        ]);

        if ($seco) {
            $this->comment('Con --dry-run no se borra nada.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Esto no se puede deshacer salvo por el respaldo. ¿Seguimos?', false)) {
            $this->comment('Cancelado.');

            return self::SUCCESS;
        }

        $ruta = $this->respaldar($empresaId, $compras, $asientos, $kardex);
        $this->info('Respaldo: ' . $ruta);

        DB::transaction(function () use ($empresaId, $compras, $asientos, $kardex, $hasta) {
            DB::table('inventory_movements')->whereIn('id', $kardex)->delete();
            DB::table('purchase_items')->whereIn('purchase_id', $compras)->delete();
            DB::table('purchases')->whereIn('id', $compras)->delete();

            // Deudas, pagos y caja no son compras: se conservan y se les quita
            // el asiento, que sí se va.
            $deudas = DB::table('debts')->where('empresa_id', $empresaId)->pluck('id');
            DB::table('debts')->whereIn('id', $deudas)->update(['journal_entry_id' => null]);
            DB::table('debt_payments')->whereIn('debt_id', $deudas)->update(['journal_entry_id' => null]);
            DB::table('cash_movements')->whereIn('journal_entry_id', $asientos)->update(['journal_entry_id' => null]);

            DB::table('journal_entry_lines')->whereIn('journal_entry_id', $asientos)->delete();
            DB::table('journal_entries')->whereIn('id', $asientos)->delete();

            // Sin kardex no hay saldo que sostener: el stock y el costo dicen
            // lo mismo que los movimientos, que es nada.
            if (! $hasta) {
                DB::table('inventory_items')->where('empresa_id', $empresaId)
                    ->update(['stock_actual' => 0, 'costo_promedio' => 0, 'saldo_valorado' => 0]);
            }
        });

        $this->newLine();
        $this->info('Listo. Como queda:');
        $this->table(['Qué', 'Quedan'], [
            ['compras', DB::table('purchases')->where('empresa_id', $empresaId)->count()],
            ['asientos', DB::table('journal_entries')->where('empresa_id', $empresaId)->count()],
            ['movimientos de kardex', DB::table('inventory_movements')->where('empresa_id', $empresaId)->count()],
            ['ítems con stock', DB::table('inventory_items')->where('empresa_id', $empresaId)->where('stock_actual', '>', 0)->count()],
        ]);

        return self::SUCCESS;
    }

    /** Todo lo que se va, en un JSON, antes de que se vaya. */
    private function respaldar(int $empresaId, $compras, $asientos, $kardex): string
    {
        $copia = [
            'empresa_id'          => $empresaId,
            'fecha'               => now()->toDateTimeString(),
            'purchases'           => DB::table('purchases')->whereIn('id', $compras)->get(),
            'purchase_items'      => DB::table('purchase_items')->whereIn('purchase_id', $compras)->get(),
            'journal_entries'     => DB::table('journal_entries')->whereIn('id', $asientos)->get(),
            'journal_entry_lines' => DB::table('journal_entry_lines')->whereIn('journal_entry_id', $asientos)->get(),
            'inventory_movements' => DB::table('inventory_movements')->whereIn('id', $kardex)->get(),
            'inventory_items'     => DB::table('inventory_items')->where('empresa_id', $empresaId)
                                        ->get(['id', 'codigo', 'nombre', 'stock_actual', 'costo_promedio', 'saldo_valorado']),
        ];

        $ruta = storage_path('app/respaldo_limpieza_' . $empresaId . '_' . date('Ymd_His') . '.json');
        file_put_contents($ruta, json_encode($copia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $ruta;
    }
}
