<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->string('estado_secundario')->nullable()->after('estado');
        });

        // Migrar estados viejos al nuevo esquema
        DB::table('logistics_packages')->where('estado', 'en_bodega')->update(['estado' => 'registrado']);
        DB::table('logistics_packages')->where('estado', 'asignado')->update(['estado' => 'embarque_solicitado']);
        DB::table('logistics_packages')->where('estado', 'entregado')->update([
            'estado'            => 'en_entrega',
            'estado_secundario' => 'entregado',
        ]);
    }

    public function down(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropColumn('estado_secundario');
        });
    }
};
