<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Procesar luego»: cuando un pedido se pospone en la corrida de producción, se
 * agenda para esta fecha y sale de la corrida de hoy. Null = entra en la corrida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->date('fecha_programada')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn('fecha_programada');
        });
    }
};
