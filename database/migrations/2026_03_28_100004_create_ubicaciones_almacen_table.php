<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('zona_id')->constrained('zonas_almacen')->cascadeOnDelete();
            // Código de posición ej: A-01-03 (pasillo-estante-nivel)
            $table->string('codigo_ubicacion', 30);
            $table->string('nombre', 150);
            // ej: Estante A, Nivel 2, Posición 3
            $table->decimal('capacidad_maxima', 10, 4)->nullable();
            $table->string('unidad_capacidad', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['zona_id', 'codigo_ubicacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_almacen');
    }
};
