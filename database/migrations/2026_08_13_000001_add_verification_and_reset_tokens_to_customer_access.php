<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens de verificación de correo y de recuperación de clave para el portal.
 *
 * No se usa la tabla `password_reset_tokens` de Laravel porque está indexada por
 * email y aquí el mismo correo puede existir en varias empresas: la clave real es
 * (empresa_id, customer_id), que ya es lo que identifica a customer_access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_access', function (Blueprint $table) {
            $table->string('verification_token', 64)->nullable()->after('email_verified_at');
            $table->timestamp('verification_sent_at')->nullable()->after('verification_token');
            $table->string('reset_token', 64)->nullable()->after('verification_sent_at');
            $table->timestamp('reset_expires_at')->nullable()->after('reset_token');

            $table->index('verification_token');
            $table->index('reset_token');
        });
    }

    public function down(): void
    {
        Schema::table('customer_access', function (Blueprint $table) {
            $table->dropIndex(['verification_token']);
            $table->dropIndex(['reset_token']);
            $table->dropColumn([
                'verification_token',
                'verification_sent_at',
                'reset_token',
                'reset_expires_at',
            ]);
        });
    }
};
