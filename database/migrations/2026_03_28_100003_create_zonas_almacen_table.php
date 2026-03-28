<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->string('codigo', 20);
            $table->string('nombre', 150);
            $table->enum('tipo', [
                'pasillo',
                'estanteria',
                'anaquel',
                'area_refrigerada',
                'camara_fria',
                'area_cuarentena',
                'area_despacho',
                'area_recepcion',
                'piso',
                'otro',
            ])->default('estanteria');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['almacen_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas_almacen');
    }
};
