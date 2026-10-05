<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hilo de cada ticket de soporte: respuestas de la empresa y de soporte, con un
 * adjunto opcional (imagen o archivo) por mensaje. Telegram manda un archivo por
 * mensaje, así que un adjunto por fila basta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remitente', 20);  // empresa | soporte
            $table->string('canal', 20);      // panel | telegram
            $table->text('mensaje')->nullable();
            $table->string('adjunto_path')->nullable();
            $table->string('adjunto_nombre')->nullable();
            $table->string('adjunto_mime', 120)->nullable();
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_mensajes');
    }
};
