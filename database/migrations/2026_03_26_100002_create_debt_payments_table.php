<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debt_amortization_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('numero')->unique();
            $table->integer('numero_cuota')->nullable();
            $table->date('fecha_pago');
            $table->decimal('monto_capital', 15, 2)->default(0);
            $table->decimal('monto_interes', 15, 2)->default(0);
            $table->decimal('monto_mora', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta'])->default('transferencia');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->string('comprobante')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
    }
};
