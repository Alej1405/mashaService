<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 — Una empresa pertenece a UN panel.
 *
 * Nullable y nullOnDelete: aditivo, no rompe nada. La columna `plan` se
 * mantiene intacta (la leen User, ServicePlan, widgets); panel_id no la
 * reemplaza todavía. Ningún código lee panel_id en Fase 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('panel_id')->nullable()->after('plan')
                ->constrained('panels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('panel_id');
        });
    }
};
