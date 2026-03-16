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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('banks');
            $table->string('numero_cuenta');
            $table->enum('tipo_cuenta', ['corriente', 'ahorros']);
            $table->string('nombre_titular');
            $table->foreignId('account_plan_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
