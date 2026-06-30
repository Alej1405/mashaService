<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El enum original limitaba plan a basic|pro|enterprise.
        // Ahora los valores válidos son dinámicos (service_plans.key).
        DB::statement('ALTER TABLE empresas DROP CONSTRAINT IF EXISTS empresas_plan_check');

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('plan', 50)->default('pro')->change();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->enum('plan', ['basic', 'pro', 'enterprise'])->default('pro')->change();
        });
    }
};
