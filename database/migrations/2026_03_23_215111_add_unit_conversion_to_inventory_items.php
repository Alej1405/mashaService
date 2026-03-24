<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // Unidad en que se COMPRA el ítem (puede diferir de la unidad de stock)
            $table->foreignId('purchase_unit_id')
                ->nullable()
                ->after('measurement_unit_id')
                ->constrained('measurement_units')
                ->nullOnDelete();

            // Cuántas unidades de stock equivalen a 1 unidad de compra
            // Ej: compro en kg (purchase_unit), stock en g (measurement_unit) → factor = 1000
            $table->decimal('conversion_factor', 15, 6)
                ->default(1)
                ->after('purchase_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_unit_id']);
            $table->dropColumn(['purchase_unit_id', 'conversion_factor']);
        });
    }
};
