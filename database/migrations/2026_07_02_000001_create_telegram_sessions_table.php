<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesiones de Telegram para la integración n8n.
 *
 * Cada fila = una sesión activa que vincula dinámicamente un chat de Telegram con
 * un usuario del ERP (y su empresa). El token vive solo aquí: revocar/vaciar esta
 * tabla suspende el acceso de n8n sin tocar nada más del ERP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_sessions', function (Blueprint $table) {
            $table->id();

            // Identidad de Telegram (capturada en el login, nunca hardcodeada).
            // Un chat = una sesión activa.
            $table->string('chat_id')->unique();

            // Usuario del ERP autenticado. Si se borra el usuario, cae su sesión.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Empresa activa elegida en el login (nullable hasta que se resuelve
            // en el caso multiempresa). Si se borra la empresa, se anula.
            $table->foreignId('empresa_id')->nullable()
                ->constrained('empresas')->nullOnDelete();

            // Hash SHA-256 del token de sesión (el texto plano solo lo tiene n8n).
            $table->string('token_hash', 64)->unique();

            // Estado conversacional opcional que n8n puede persistir entre pasos.
            $table->string('estado')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_sessions');
    }
};
