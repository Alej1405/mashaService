<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->decimal('cobro_nacionalizacion', 10, 2)->nullable()->after('unidad_cobro');
            $table->decimal('cobro_transporte_interno', 10, 2)->nullable()->after('cobro_nacionalizacion');
            $table->decimal('cobro_otro', 10, 2)->nullable()->after('cobro_transporte_interno');
            $table->string('cobro_otro_descripcion')->nullable()->after('cobro_otro');
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn([
                'cobro_nacionalizacion',
                'cobro_transporte_interno',
                'cobro_otro',
                'cobro_otro_descripcion',
            ]);
        });
    }
};
