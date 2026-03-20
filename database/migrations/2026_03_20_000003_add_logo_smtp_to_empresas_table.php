<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('mailgun_from_name')
                  ->comment('Ruta del logo en disco public');

            $table->string('smtp_host')->nullable()->after('logo_path');
            $table->unsignedSmallInteger('smtp_port')->nullable()->after('smtp_host')->default(587);
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->string('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption')->nullable()->after('smtp_password')->default('tls');
            $table->string('smtp_from_email')->nullable()->after('smtp_encryption');
            $table->string('smtp_from_name')->nullable()->after('smtp_from_email');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'smtp_host', 'smtp_port', 'smtp_username',
                'smtp_password', 'smtp_encryption',
                'smtp_from_email', 'smtp_from_name',
            ]);
        });
    }
};
