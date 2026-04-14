<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_design_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre_servicio');
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['activo', 'pausado', 'finalizado'])->default('activo');
            $table->decimal('precio', 12, 2)->nullable();
            $table->string('periodicidad')->nullable(); // mensual, anual, único, etc.
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_contracts');
    }
};
