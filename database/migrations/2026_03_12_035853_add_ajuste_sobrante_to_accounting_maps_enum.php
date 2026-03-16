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
        DB::statement("ALTER TABLE accounting_maps MODIFY COLUMN tipo_movimiento ENUM(
            'compra_contado',
            'compra_credito_local',
            'compra_credito_exterior',
            'venta_contado',
            'venta_credito',
            'consumo_produccion',
            'costo_venta',
            'iva_compras',
            'iva_ventas',
            'depreciacion',
            'ajuste_inventario',
            'ajuste_sobrante'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_maps', function (Blueprint $table) {
            //
        });
    }
};
