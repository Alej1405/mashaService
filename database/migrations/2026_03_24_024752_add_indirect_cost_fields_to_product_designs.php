<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->decimal('capacidad_instalada_mensual', 15, 2)->nullable()->after('notas_estrategicas');
            $table->unsignedSmallInteger('dias_laborales_mes')->default(22)->after('capacidad_instalada_mensual');
            $table->unsignedSmallInteger('num_personas')->nullable()->after('dias_laborales_mes');
            $table->decimal('costo_mano_obra_persona', 12, 2)->nullable()->after('num_personas');
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropColumn([
                'capacidad_instalada_mensual',
                'dias_laborales_mes',
                'num_personas',
                'costo_mano_obra_persona',
            ]);
        });
    }
};
