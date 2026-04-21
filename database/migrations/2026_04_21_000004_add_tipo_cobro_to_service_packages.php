<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->string('cobro_nacionalizacion_tipo', 10)->default('tramite')->after('cobro_nacionalizacion');
            $table->string('cobro_transporte_interno_tipo', 10)->default('tramite')->after('cobro_transporte_interno');
            $table->string('cobro_otro_tipo', 10)->default('tramite')->after('cobro_otro');
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn([
                'cobro_nacionalizacion_tipo',
                'cobro_transporte_interno_tipo',
                'cobro_otro_tipo',
            ]);
        });
    }
};
