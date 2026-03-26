<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('numero')->unique();
            $table->enum('tipo', ['prestamo_bancario', 'tarjeta_credito', 'prestamo_personal', 'prestamo_empresarial', 'otro'])->default('prestamo_bancario');
            $table->string('acreedor');
            $table->text('descripcion');
            $table->decimal('monto_original', 15, 2);
            $table->decimal('tasa_interes', 8, 4)->default(0);
            $table->enum('tipo_tasa', ['simple', 'compuesto'])->default('simple');
            $table->enum('frecuencia_tasa', ['mensual', 'anual'])->default('anual');
            $table->date('fecha_inicio');
            $table->integer('plazo_meses')->nullable();
            $table->date('fecha_vencimiento');
            $table->integer('numero_cuotas')->nullable();
            $table->enum('clasificacion', ['corriente', 'no_corriente'])->default('corriente');
            $table->string('cuenta_pago_acreedor')->nullable();
            $table->string('banco_acreedor')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('credit_card_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('estado', ['borrador', 'activa', 'parcial', 'pagada', 'vencida', 'refinanciada'])->default('borrador');
            $table->decimal('saldo_pendiente', 15, 2)->default(0);
            $table->decimal('total_pagado', 15, 2)->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
