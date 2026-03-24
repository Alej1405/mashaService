<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_design_id')->constrained('product_designs')->cascadeOnDelete();
            $table->string('nombre');
            $table->foreignId('measurement_unit_id')->nullable()->constrained('measurement_units')->nullOnDelete();
            $table->decimal('costo_estimado', 10, 4)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_presentations');
    }
};
