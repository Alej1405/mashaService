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
        // Grupo en contactos
        Schema::table('mailing_contacts', function (Blueprint $table) {
            $table->foreignId('mailing_group_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('mailing_groups')
                ->nullOnDelete();
        });

        // Grupo en campañas
        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->foreignId('mailing_group_id')
                ->nullable()
                ->after('mail_template_id')
                ->constrained('mailing_groups')
                ->nullOnDelete();
        });

        // Cuota mensual y día de renovación en empresas
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedInteger('mailing_monthly_limit')->default(3000)->after('mailgun_from_name');
            $table->unsignedTinyInteger('mailing_billing_day')->default(1)->after('mailing_monthly_limit');
        });
    }

    public function down(): void
    {
        Schema::table('mailing_contacts', function (Blueprint $table) {
            $table->dropForeign(['mailing_group_id']);
            $table->dropColumn('mailing_group_id');
        });

        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->dropForeign(['mailing_group_id']);
            $table->dropColumn('mailing_group_id');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['mailing_monthly_limit', 'mailing_billing_day']);
        });
    }
};
