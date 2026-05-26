<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_presentations', function (Blueprint $table) {
            if (! Schema::hasColumn('item_presentations', 'measurement_unit_id')) {
                $table->foreignId('measurement_unit_id')
                    ->nullable()
                    ->after('nombre')
                    ->constrained('measurement_units')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('item_presentations', 'capacidad')) {
                $table->decimal('capacidad', 12, 4)
                    ->nullable()
                    ->after('measurement_unit_id')
                    ->comment('Cantidad de unidades que contiene la presentación');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_presentations', function (Blueprint $table) {
            $table->dropForeign(['measurement_unit_id']);
            $table->dropColumn(['measurement_unit_id', 'capacidad']);
        });
    }
};
