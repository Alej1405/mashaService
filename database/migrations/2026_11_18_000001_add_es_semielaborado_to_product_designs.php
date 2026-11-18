<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materia prima procesada (semielaborado): un diseño que se fabrica con su
 * receta y solo se usa dentro de otras fórmulas. No se vende, así que no
 * genera producto de tienda y su ítem de inventario es materia prima, no
 * producto terminado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->boolean('es_semielaborado')->default(false)->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropColumn('es_semielaborado');
        });
    }
};
