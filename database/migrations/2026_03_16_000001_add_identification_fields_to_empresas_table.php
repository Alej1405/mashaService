<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->enum('tipo_persona', ['natural', 'juridica'])->nullable()->after('email');
            $table->enum('tipo_identificacion', ['ruc', 'cedula', 'pasaporte'])->nullable()->after('tipo_persona');
            $table->string('numero_identificacion', 20)->nullable()->after('tipo_identificacion');
            $table->string('direccion')->nullable()->after('numero_identificacion');
            $table->string('actividad_economica')->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_persona',
                'tipo_identificacion',
                'numero_identificacion',
                'direccion',
                'actividad_economica',
            ]);
        });
    }
};
