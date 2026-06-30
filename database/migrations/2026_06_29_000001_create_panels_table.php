<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 — Paneles dinámicos.
 *
 * Un "panel" es un contenedor de VISIBILIDAD: define qué módulos se muestran
 * en el menú. Los módulos siempre funcionan en segundo plano; el panel solo
 * decide qué se ve. Es dinámico: crear un panel nuevo = insertar una fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // basic, pro, enterprise, prueba, logistica...
            $table->string('name');                    // nombre visible
            $table->string('path')->nullable();        // path del PanelProvider Filament asociado (app, pro...)
            $table->string('color')->nullable();       // color de marca opcional
            $table->string('icon')->nullable();        // heroicon opcional
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panels');
    }
};
