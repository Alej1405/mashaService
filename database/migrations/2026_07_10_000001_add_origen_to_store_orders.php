<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un pedido (store_orders) puede llegar de varias fuentes a la MISMA base.
 * `origen` registra de dónde nació: cliente (portal), tienda (admin) o erp.
 * Los estados NO se gestionan aquí; se reportan desde la orden de trabajo (producción).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('store_orders', 'origen')) {
                $table->string('origen', 20)->default('cliente')->after('numero');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE store_orders ADD CONSTRAINT store_orders_origen_check CHECK (origen IN ('cliente','tienda','erp'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE store_orders DROP CONSTRAINT IF EXISTS store_orders_origen_check');
        }
        Schema::table('store_orders', function (Blueprint $table) {
            if (Schema::hasColumn('store_orders', 'origen')) {
                $table->dropColumn('origen');
            }
        });
    }
};
