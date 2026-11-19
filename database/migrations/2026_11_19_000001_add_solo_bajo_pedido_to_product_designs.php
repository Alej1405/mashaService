<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Política de preparación de un semielaborado.
 *
 * Marcado: no se mantiene en stock; se prepara fresco cada vez que un pedido lo
 * necesita (la corrida prepara la cantidad del pedido, sin descontar stock).
 * Sin marcar: se prepara solo el faltante respecto al stock disponible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->boolean('solo_bajo_pedido')->default(false)->after('es_semielaborado');
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropColumn('solo_bajo_pedido');
        });
    }
};
