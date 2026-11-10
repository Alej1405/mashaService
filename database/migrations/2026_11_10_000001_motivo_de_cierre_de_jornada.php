<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Por qué se paró una jornada: almuerzo, fin del día, falta de materia prima… */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produccion_jornadas', function (Blueprint $table) {
            $table->string('motivo_cierre', 255)->nullable()->after('fin');
        });
    }

    public function down(): void
    {
        Schema::table('produccion_jornadas', function (Blueprint $table) {
            $table->dropColumn('motivo_cierre');
        });
    }
};
