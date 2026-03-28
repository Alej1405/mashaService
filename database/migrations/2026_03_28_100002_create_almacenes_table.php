<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 20);
            $table->string('nombre', 150);
            $table->enum('tipo', [
                'bodega_propia',
                'deposito_externo',
                'area_produccion',
                'punto_venta',
                'transito',
            ])->default('bodega_propia');
            $table->text('descripcion')->nullable();
            $table->string('direccion')->nullable();
            $table->string('responsable')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacenes');
    }
};
