<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un producto de la tienda es general (lo ve y lo pide cualquier cliente) o
 * exclusivo de un cliente de la empresa (solo él lo ve y lo pide).
 * Un exclusivo sin cliente no tiene sentido: la base lo impide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->string('visibilidad', 10)->default('general')->after('publicado');
            // No se puede borrar un cliente que tiene productos exclusivos.
            $table->foreignId('customer_id')->nullable()->after('visibilidad')
                ->constrained('customers')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE store_products ADD CONSTRAINT store_products_visibilidad_check CHECK (
            (visibilidad = 'general' AND customer_id IS NULL) OR (visibilidad = 'exclusivo' AND customer_id IS NOT NULL))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE store_products DROP CONSTRAINT IF EXISTS store_products_visibilidad_check');

        Schema::table('store_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('visibilidad');
        });
    }
};
