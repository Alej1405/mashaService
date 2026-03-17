<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->enum('plan', ['basic', 'pro', 'enterprise'])
                  ->default('pro')
                  ->after('activo')
                  ->comment('Plan de suscripción del tenant');

            $table->string('mailgun_api_key')->nullable()->after('plan');
            $table->string('mailgun_domain')->nullable()->after('mailgun_api_key');
            $table->string('mailgun_from_email')->nullable()->after('mailgun_domain');
            $table->string('mailgun_from_name')->nullable()->after('mailgun_from_email');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['plan', 'mailgun_api_key', 'mailgun_domain', 'mailgun_from_email', 'mailgun_from_name']);
        });
    }
};
