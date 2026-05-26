<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->string('tipo_produccion')->nullable()->change();
            $table->string('estado', 30)->default('en_proyecto')->after('fecha_fin');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('production_plan_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('production_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_plan_id');
        });
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
