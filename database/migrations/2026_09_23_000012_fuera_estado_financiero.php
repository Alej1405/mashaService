<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fuera `account_plans.estado_financiero`.
 *
 * Era el tercer campo que decía a qué línea suma una cuenta, junto a
 * `linea_estado` —ya eliminada— y `codigo_supercias`. Ninguno de sus valores
 * casaba con el catálogo de la Superintendencia, así que los estados
 * financieros salían en cero mientras las tres columnas parecían llenas.
 *
 * Marco: skill `contabilidad-ec` — un concepto, una columna.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('account_plans', 'estado_financiero')) {
            Schema::table('account_plans', function (Blueprint $table) {
                $table->dropColumn('estado_financiero');
            });
        }
    }

    public function down(): void
    {
        Schema::table('account_plans', function (Blueprint $table) {
            $table->string('estado_financiero', 60)->nullable();
        });
    }
};
