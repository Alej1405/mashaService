<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_send_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->index();
            $table->string('email')->index();
            $table->string('tipo');           // carta_presentacion | noticia | campana
            $table->unsignedBigInteger('referencia_id')->nullable()->index(); // post_id | campaign_id
            $table->timestamp('sent_at')->useCurrent();

            // Índice compuesto para acelerar el chequeo de duplicados
            $table->index(['empresa_id', 'email', 'tipo', 'referencia_id'], 'msl_dedup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_send_log');
    }
};
