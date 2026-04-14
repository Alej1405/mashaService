<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_bodegas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('pais'); // 'EEUU' | 'ESPANA'
            $table->string('nombre');
            $table->text('direccion_origen')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('estado_provincia')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('empresa_aliada')->nullable();
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_email')->nullable();
            $table->string('contacto_telefono')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'pais']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_bodegas');
    }
};
