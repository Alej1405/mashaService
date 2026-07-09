<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina columnas muertas de store_products: product_design_id y
 * product_presentation_id. Ya no las usa ningún flujo (el producto vive solo en
 * store_products; el vínculo al diseño/costos se retiró).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE store_products DROP CONSTRAINT IF EXISTS store_products_product_design_id_foreign');
            DB::statement('ALTER TABLE store_products DROP CONSTRAINT IF EXISTS store_products_product_presentation_id_foreign');
        }

        Schema::table('store_products', function (Blueprint $table) {
            if (Schema::hasColumn('store_products', 'product_design_id')) {
                $table->dropColumn('product_design_id');
            }
            if (Schema::hasColumn('store_products', 'product_presentation_id')) {
                $table->dropColumn('product_presentation_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            if (! Schema::hasColumn('store_products', 'product_design_id')) {
                $table->unsignedBigInteger('product_design_id')->nullable();
            }
            if (! Schema::hasColumn('store_products', 'product_presentation_id')) {
                $table->unsignedBigInteger('product_presentation_id')->nullable();
            }
        });
    }
};
