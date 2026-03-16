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
        // Normalizar datos existentes antes de cambiar el ENUM
        DB::table('sale_items')->where('tipo_item', 'producto')->update(['tipo_item' => 'producto_terminado']);

        Schema::table('sale_items', function (Blueprint $table) {
            $table->enum('tipo_item', [
                'producto_terminado',
                'materia_prima',
                'insumo',
                'servicio',
                'activo_fijo_maquinaria',
                'activo_fijo_computo',
                'activo_fijo_vehiculo',
                'activo_fijo_muebles',
            ])->default('producto_terminado')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->enum('tipo_item', ['producto', 'servicio'])->default('producto')->change();
        });
    }
};
