<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Reemplazar enum por varchar para soportar 'abastecimiento' y flexibilidad futura
            $table->string('estado', 30)->default('borrador')->change();

            // Fecha de finalización por etapa (fecha ya existente = fecha_inicio)
            $table->date('fecha_fin')->nullable()->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('fecha_fin');
            $table->enum('estado', ['borrador', 'completado', 'anulado'])->default('borrador')->change();
        });
    }
};
