<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_customers', function (Blueprint $table) {
            // persona | empresa
            $table->string('tipo')->default('persona')->after('empresa_id');
            // Razón social cuando tipo = empresa
            $table->string('razon_social')->nullable()->after('tipo');
            // Cédula, RUC o pasaporte — también sirve como contraseña inicial
            $table->string('cedula_ruc')->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('store_customers', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'razon_social', 'cedula_ruc']);
        });
    }
};
