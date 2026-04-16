<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar gastos_envio e impuestos_amazon al paquete (no al ítem)
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->decimal('gastos_envio', 10, 2)->nullable()->after('valor_declarado');
            $table->decimal('impuestos_amazon', 10, 2)->nullable()->after('gastos_envio');
        });

        // Quitarlos de la tabla de ítems
        Schema::table('logistics_package_items', function (Blueprint $table) {
            $table->dropColumn(['gastos_envio', 'impuestos_amazon']);
        });
    }

    public function down(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropColumn(['gastos_envio', 'impuestos_amazon']);
        });

        Schema::table('logistics_package_items', function (Blueprint $table) {
            $table->decimal('gastos_envio', 10, 2)->nullable();
            $table->decimal('impuestos_amazon', 10, 2)->nullable();
        });
    }
};
