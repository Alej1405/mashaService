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
        Schema::create('system_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->enum('tipo', ['error', 'warning', 'info', 'job_fallido'])->default('error');
            $table->string('modulo')->nullable();
            $table->string('titulo');
            $table->text('mensaje');
            $table->json('contexto')->nullable();
            $table->boolean('resuelto')->default(false);
            $table->timestamp('resuelto_at')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['empresa_id', 'resuelto']);
            $table->index(['tipo', 'resuelto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};
