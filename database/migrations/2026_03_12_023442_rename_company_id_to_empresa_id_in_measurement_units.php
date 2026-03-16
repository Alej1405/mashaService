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
        Schema::table('measurement_units', function (Blueprint $table) {
            if (Schema::hasColumn('measurement_units', 'empresa_id')) {
                $table->renameColumn('empresa_id', 'empresa_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measurement_units', function (Blueprint $table) {
            if (Schema::hasColumn('measurement_units', 'empresa_id')) {
                $table->renameColumn('empresa_id', 'empresa_id');
            }
        });
    }
};
