<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('tiene_logistica')->default(false);
            $table->boolean('tiene_comercio_exterior')->default(false);
            $table->boolean('tipo_operacion_productos')->default(true);
            $table->boolean('tipo_operacion_servicios')->default(false);
            $table->boolean('tipo_operacion_manufactura')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'tiene_logistica',
                'tiene_comercio_exterior',
                'tipo_operacion_productos',
                'tipo_operacion_servicios',
                'tipo_operacion_manufactura',
            ]);
        });
    }
};
