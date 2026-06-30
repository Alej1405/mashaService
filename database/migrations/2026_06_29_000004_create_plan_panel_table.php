<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3 — Relación N:M plan ↔ panel.
 *
 * Un plan (service_plan) abre uno o más paneles. El acceso a un panel
 * deja de decidirse por niveles cableados (User::PLAN_LEVELS) y pasa a
 * consultar esta relación: "¿el plan de mi empresa incluye este panel?".
 *
 * Agregar/quitar un panel de un plan = una fila. Administrable desde el admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_panel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_plan_id')->constrained('service_plans')->cascadeOnDelete();
            $table->foreignId('panel_id')->constrained('panels')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_plan_id', 'panel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_panel');
    }
};
