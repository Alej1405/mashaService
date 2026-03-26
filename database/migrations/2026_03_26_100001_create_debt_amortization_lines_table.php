<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_amortization_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $table->integer('numero_cuota');
            $table->date('fecha_vencimiento');
            $table->decimal('saldo_inicial', 15, 2);
            $table->decimal('monto_capital', 15, 2);
            $table->decimal('monto_interes', 15, 2);
            $table->decimal('total_cuota', 15, 2);
            $table->decimal('saldo_final', 15, 2);
            $table->enum('estado', ['pendiente', 'pagada', 'vencida'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_amortization_lines');
    }
};
