<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teléfono del usuario en formato internacional (+593979000505), como lo entrega
 * Telegram al compartir el contacto, y el chat de Telegram ligado a ese número.
 * El chat queda en el usuario, no en la sesión: los avisos llegan aunque la
 * sesión del bot haya vencido. Un usuario puede tener varias empresas; el chat es
 * de la persona y cada aviso dice de qué empresa es.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->unique()->after('email');
            $table->string('telegram_chat_id', 32)->nullable()->unique()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'telegram_chat_id']);
        });
    }
};
