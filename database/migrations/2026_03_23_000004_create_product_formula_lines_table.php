<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_formula_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presentation_id')->constrained('product_presentations')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('item_request_id')->nullable()->constrained('item_requests')->nullOnDelete();
            $table->decimal('cantidad', 14, 6);
            $table->foreignId('measurement_unit_id')->nullable()->constrained('measurement_units')->nullOnDelete();
            $table->boolean('es_subproducto_manufacturado')->default(false);
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_formula_lines');
    }
};
