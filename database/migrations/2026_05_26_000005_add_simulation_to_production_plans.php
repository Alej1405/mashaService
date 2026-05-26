<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->foreignId('product_simulation_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('product_simulations')
                ->nullOnDelete();

            // Hacer nullable las columnas polimórficas anteriores
            $table->string('designable_type')->nullable()->change();
            $table->unsignedBigInteger('designable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_simulation_id');
            $table->string('designable_type')->nullable(false)->change();
            $table->unsignedBigInteger('designable_id')->nullable(false)->change();
        });
    }
};
