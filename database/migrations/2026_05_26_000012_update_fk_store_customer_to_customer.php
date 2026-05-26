<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paso 3/4: Redirige todos los FK store_customer_id → customers.id
 *
 * Tablas afectadas:
 *   - store_customer_companies  (3 registros)
 *   - logistics_packages        (15 registros, ya tiene customer_id parcialmente)
 *   - logistics_payment_claims  (2 registros)
 *   - logistics_billing_requests(10 registros)
 *   - store_addresses           (0 registros)
 *   - store_orders              (0 registros)
 *   - service_contracts         (0 registros)
 *
 * Estrategia: para cada tabla con datos, primero llenamos/verificamos customer_id
 * a partir del mapping store_customers.customer_id; luego eliminamos la columna vieja.
 */
return new class extends Migration
{
    /** Mapping: store_customer_id → customer_id (obtenido de store_customers.customer_id) */
    private function buildMap(): array
    {
        $map = [];
        $rows = DB::table('store_customers')->whereNotNull('customer_id')->get(['id', 'customer_id']);
        foreach ($rows as $r) {
            $map[(int) $r->id] = (int) $r->customer_id;
        }
        return $map;
    }

    public function up(): void
    {
        $map = $this->buildMap();

        // ── 1. store_customer_companies ─────────────────────────────────────────
        Schema::table('store_customer_companies', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('store_customer_id');
        });

        foreach (DB::table('store_customer_companies')->get() as $row) {
            $customerId = $map[(int) $row->store_customer_id] ?? null;
            if ($customerId) {
                DB::table('store_customer_companies')
                    ->where('id', $row->id)
                    ->update(['customer_id' => $customerId]);
            }
        }

        Schema::table('store_customer_companies', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->dropConstrainedForeignId('store_customer_id');
        });

        // ── 2. logistics_packages ────────────────────────────────────────────────
        // Ya tiene customer_id; solo completamos los nulos y eliminamos store_customer_id
        foreach (DB::table('logistics_packages')->whereNotNull('store_customer_id')->get() as $row) {
            if (! $row->customer_id) {
                $customerId = $map[(int) $row->store_customer_id] ?? null;
                if ($customerId) {
                    DB::table('logistics_packages')
                        ->where('id', $row->id)
                        ->update(['customer_id' => $customerId]);
                }
            }
        }

        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_customer_id');
        });

        // ── 3. logistics_payment_claims ──────────────────────────────────────────
        $this->migrateTable('logistics_payment_claims', $map);

        // ── 4. logistics_billing_requests ────────────────────────────────────────
        $this->migrateTable('logistics_billing_requests', $map);

        // ── 5. Tablas vacías: solo renombrar FK (store_addresses, store_orders, service_contracts)
        foreach (['store_addresses', 'store_orders', 'service_contracts'] as $table) {
            $this->migrateTable($table, $map);
        }
    }

    /** Agrega customer_id, migra datos, elimina store_customer_id */
    private function migrateTable(string $table, array $map): void
    {
        Schema::table($table, function (Blueprint $t) {
            $t->unsignedBigInteger('customer_id')->nullable()->after('store_customer_id');
        });

        foreach (DB::table($table)->whereNotNull('store_customer_id')->get(['id', 'store_customer_id']) as $row) {
            $customerId = $map[(int) $row->store_customer_id] ?? null;
            if ($customerId) {
                DB::table($table)->where('id', $row->id)->update(['customer_id' => $customerId]);
            }
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            $t->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $t->dropConstrainedForeignId('store_customer_id');
        });
    }

    public function down(): void
    {
        // Reversión manual requerida — restaurar desde backup.
    }
};
