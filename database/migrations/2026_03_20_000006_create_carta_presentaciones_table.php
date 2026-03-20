<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carta_presentaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('asunto')->default('Carta de Presentación');
            $table->string('saludo')->default('Estimado/a,');
            $table->text('intro');
            $table->string('servicios_titulo')->default('Nuestros servicios');
            $table->text('cierre');
            $table->string('firma_nombre');
            $table->string('firma_cargo')->nullable();
            $table->string('color_primario')->default('#1e3a5f');
            $table->string('color_acento')->default('#e8a045');
            $table->string('color_texto')->default('#2d2d2d');
            $table->string('color_fondo')->default('#f5f7fa');
            $table->timestamps();
            $table->unique('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carta_presentaciones');
    }
};
