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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('numero');
            $table->date('fecha');
            $table->text('descripcion');
            $table->enum('tipo', ['apertura', 'manual', 'compra', 'venta', 'manufactura', 'ajuste', 'cierre', 'depreciacion']);
            $table->enum('origen', ['manual', 'automatico'])->default('manual');
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->enum('status', ['borrador', 'confirmado', 'anulado'])->default('borrador');
            $table->decimal('total_debe', 14, 2)->default(0);
            $table->decimal('total_haber', 14, 2)->default(0);
            $table->boolean('esta_cuadrado')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('confirmado_por')->nullable()->constrained('users');
            $table->timestamp('confirmado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users');
            $table->timestamp('anulado_at')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
