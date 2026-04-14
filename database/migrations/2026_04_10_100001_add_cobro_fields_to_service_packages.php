<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->string('base_cobro')->default('fijo')->after('precio_estimado');
            // fijo, peso, volumen, distancia, tiempo, paginas, tramite, sesion, unidad, otro
            $table->string('unidad_cobro')->nullable()->after('base_cobro');
            // kg, g, lb, t, l, ml, m3, cm3, km, m, h, min, pag, etc.
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn(['base_cobro', 'unidad_cobro']);
        });
    }
};
