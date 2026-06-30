<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 — Módulos visibles por panel (pivote relacional, NO JSONB).
 *
 * Cada fila asocia un panel con una clave de módulo del catálogo
 * config('erp_features'). Agregar/quitar un módulo de un panel = una fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('panels')->cascadeOnDelete();
            $table->string('module_key');   // finanzas, tesoreria, ventas, marketing, tienda, logistica...
            $table->timestamps();

            $table->unique(['panel_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_modules');
    }
};
