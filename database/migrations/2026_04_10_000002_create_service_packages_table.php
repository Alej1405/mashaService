<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_design_id')->constrained('service_designs')->cascadeOnDelete();
            $table->string('nombre')->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('duracion_estimada', 8, 2)->nullable(); // duración por sesión/entrega
            $table->string('duracion_unidad')->default('horas'); // horas, dias, semanas
            $table->boolean('activo')->default(true);
            $table->decimal('margen_objetivo', 8, 2)->nullable();
            $table->decimal('precio_estimado', 12, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
