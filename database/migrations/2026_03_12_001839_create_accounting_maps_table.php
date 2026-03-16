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
        Schema::create('accounting_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            
            $table->enum('tipo_item', [
                'insumo', 
                'materia_prima', 
                'producto_en_proceso',
                'producto_terminado', 
                'activo_fijo_maquinaria', 
                'activo_fijo_computo', 
                'activo_fijo_vehiculo',
                'activo_fijo_muebles', 
                'servicio',
                'global'
            ]);
            
            $table->enum('tipo_movimiento', [
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
                'ajuste_inventario'
            ]);
            
            $table->foreignId('account_plan_id')->constrained('account_plans')->cascadeOnDelete();
            $table->timestamps();

            // Índice único para evitar duplicados en la misma empresa
            $table->unique(['empresa_id', 'tipo_item', 'tipo_movimiento'], 'accounting_map_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_maps');
    }
};
