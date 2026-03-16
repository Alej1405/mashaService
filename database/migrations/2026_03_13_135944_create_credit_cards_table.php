<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('ultimos_digitos', 4)->nullable();
            $table->enum('franquicia', ['visa', 'mastercard', 'amex', 'diners'])
                  ->default('visa');
            $table->decimal('limite_credito', 14, 2)->default(0);
            $table->decimal('saldo_utilizado', 14, 2)->default(0);
            $table->integer('dia_corte')->default(1);
            $table->integer('dia_pago')->default(15);
            $table->foreignId('account_plan_id')->nullable()
                  ->constrained('account_plans')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
