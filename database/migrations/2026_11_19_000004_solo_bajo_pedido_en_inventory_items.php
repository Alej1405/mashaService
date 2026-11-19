<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Política de preparación de una materia prima procesada, ahora en el ítem (se crea
 * desde Inventario). Marcado: no se mantiene en stock, se prepara fresco contra pedido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('solo_bajo_pedido')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('solo_bajo_pedido');
        });
    }
};
