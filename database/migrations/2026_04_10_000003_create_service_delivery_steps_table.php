<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_delivery_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_design_id')->constrained('service_designs')->cascadeOnDelete();
            $table->integer('orden')->default(0);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('tiempo_estimado_horas', 8, 2)->nullable();
            $table->string('responsable')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_delivery_steps');
    }
};
