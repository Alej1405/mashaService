<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_formula_lines', function (Blueprint $table) {
            $table->decimal('costo_estimado', 14, 4)->default(0)->after('es_subproducto_manufacturado');
        });
    }

    public function down(): void
    {
        Schema::table('product_formula_lines', function (Blueprint $table) {
            $table->dropColumn('costo_estimado');
        });
    }
};
