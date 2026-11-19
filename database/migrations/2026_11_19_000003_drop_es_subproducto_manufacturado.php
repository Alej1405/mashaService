<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El marcador «Subproducto» de la línea de fórmula no lo usaba ninguna lógica: un
 * insumo que es materia prima procesada ya se reconoce por su tipo. Se elimina para
 * no dejar basura ni dos formas de decir lo mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_formula_lines', function (Blueprint $table) {
            $table->dropColumn('es_subproducto_manufacturado');
        });
    }

    public function down(): void
    {
        Schema::table('product_formula_lines', function (Blueprint $table) {
            $table->boolean('es_subproducto_manufacturado')->default(false);
        });
    }
};
