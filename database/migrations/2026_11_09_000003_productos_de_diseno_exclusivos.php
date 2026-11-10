<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un producto (diseño) también puede ser de un solo cliente: Tequila Rivet solo
 * para Fabricio. Sus presentaciones en la tienda heredan esa visibilidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->string('visibilidad', 10)->default('general')->after('store_category_id');
            $table->foreignId('customer_id')->nullable()->after('visibilidad')
                ->constrained('customers')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE product_designs ADD CONSTRAINT product_designs_visibilidad_check CHECK (
            (visibilidad = 'general' AND customer_id IS NULL) OR (visibilidad = 'exclusivo' AND customer_id IS NOT NULL))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_designs DROP CONSTRAINT IF EXISTS product_designs_visibilidad_check');

        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('visibilidad');
        });
    }
};
