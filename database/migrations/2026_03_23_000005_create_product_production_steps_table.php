<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_production_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_design_id')->constrained('product_designs')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('tiempo_estimado_minutos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_production_steps');
    }
};
