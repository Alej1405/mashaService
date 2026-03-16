<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_session_id')->nullable()
                  ->constrained()->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()
                  ->constrained()->nullOnDelete();
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->enum('origen', [
                'venta', 'compra', 'transferencia',
                'ajuste', 'apertura', 'cierre', 'manual'
            ])->default('manual');
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->decimal('monto', 14, 2);
            $table->string('descripcion');
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
