<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->enum('type', ['activo', 'pasivo', 'patrimonio', 'ingreso', 'costo', 'gasto']);
            $table->enum('nature', ['deudora', 'acreedora']);
            $table->string('parent_code')->nullable();
            $table->integer('level');
            $table->boolean('accepts_movements')->default(false);
            $table->enum('modulo', ['base', 'logistica', 'comercio_exterior', 'productos', 'servicios', 'manufactura'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_plans');
    }
};
