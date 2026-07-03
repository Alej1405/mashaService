<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la columna residual `inventory_item_id` de `store_products`.
 *
 * Era un remanente de la migración MariaDB→PostgreSQL (solo existe en prod, NOT
 * NULL): el modelo no la usa (el vínculo a inventario es vía product_design_id) y
 * bloqueaba la creación de productos. En local ni existe → la guarda hace no-op.
 * store_products tiene 0 filas, sin impacto de datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('store_products', 'inventory_item_id')) {
            Schema::table('store_products', function (Blueprint $table) {
                $table->dropColumn('inventory_item_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('store_products', 'inventory_item_id')) {
            Schema::table('store_products', function (Blueprint $table) {
                $table->foreignId('inventory_item_id')->nullable();
            });
        }
    }
};
