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
        Schema::table('product_presentations', function (Blueprint $table) {
            $table->decimal('cantidad_minima_produccion', 15, 4)->default(1)->after('activa');
            $table->decimal('margen_objetivo', 5, 2)->default(30)->after('cantidad_minima_produccion'); // %
            $table->decimal('pvp_estimado', 15, 4)->default(0)->after('margen_objetivo');
        });
    }

    public function down(): void
    {
        Schema::table('product_presentations', function (Blueprint $table) {
            $table->dropColumn(['cantidad_minima_produccion', 'margen_objetivo', 'pvp_estimado']);
        });
    }
};
