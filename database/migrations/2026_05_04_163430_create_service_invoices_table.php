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
        Schema::create('service_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('numero')->unique();
            $table->string('periodo');
            $table->enum('plan', ['basic', 'pro', 'enterprise']);
            $table->decimal('monto', 10, 2);
            $table->enum('estado', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->string('metodo_pago')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_invoices');
    }
};
