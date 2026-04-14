<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_indirect_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_design_id')->constrained('service_designs')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('descripcion')->nullable();
            $table->decimal('monto_mensual', 12, 2);
            $table->enum('frecuencia', ['semanal', 'mensual', 'unico'])->default('mensual');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_indirect_costs');
    }
};
