<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carta_presentaciones', function (Blueprint $table) {
            $table->boolean('mostrar_servicios')->default(true)->after('servicios_titulo');
            $table->boolean('mostrar_equipo')->default(true)->after('mostrar_servicios');
            $table->boolean('mostrar_contacto')->default(true)->after('mostrar_equipo');
        });
    }

    public function down(): void
    {
        Schema::table('carta_presentaciones', function (Blueprint $table) {
            $table->dropColumn(['mostrar_servicios', 'mostrar_equipo', 'mostrar_contacto']);
        });
    }
};
