<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->decimal('pvp_venta', 10, 4)->nullable()->after('cantidad_minima_distribuidor');
            $table->boolean('pvp_incluye_iva')->default(false)->after('pvp_venta');
            $table->decimal('margen_venta', 8, 2)->nullable()->after('pvp_incluye_iva');
            $table->unsignedInteger('dias_venta')->nullable()->after('margen_venta');
            $table->decimal('meta_ganancia', 8, 2)->default(5)->after('dias_venta');
            $table->boolean('aplica_ice')->default(false)->after('meta_ganancia');
            $table->string('ice_categoria', 50)->nullable()->after('aplica_ice');
            $table->decimal('ice_porcentaje', 8, 2)->nullable()->after('ice_categoria');
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropColumn([
                'pvp_venta', 'pvp_incluye_iva', 'margen_venta', 'dias_venta',
                'meta_ganancia', 'aplica_ice', 'ice_categoria', 'ice_porcentaje',
            ]);
        });
    }
};
