<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('service_design_id')->constrained('service_designs')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('package_nombre')->nullable();
            $table->decimal('cantidad', 10, 2); // sessions/hours/clients
            $table->decimal('precio_sin_iva', 12, 4);
            $table->decimal('margen_porcentaje', 8, 2)->nullable();
            $table->integer('dias_entrega')->nullable();
            $table->decimal('meta_ganancia', 8, 2)->nullable();
            $table->decimal('costo_total', 12, 2)->nullable();
            $table->decimal('ingreso_neto', 12, 2)->nullable();
            $table->decimal('utilidad_bruta', 12, 2)->nullable();
            $table->decimal('utilidad_neta', 12, 2)->nullable();
            $table->decimal('margen_bruto', 8, 2)->nullable();
            $table->decimal('margen_neto', 8, 2)->nullable();
            $table->decimal('roi', 8, 2)->nullable();
            $table->integer('payback_dias')->nullable();
            $table->enum('estado', ['en_proyecto', 'ejecutado', 'cancelado'])->default('en_proyecto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_simulations');
    }
};
