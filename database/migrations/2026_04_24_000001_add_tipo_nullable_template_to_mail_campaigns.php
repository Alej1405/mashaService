<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->string('tipo')->default('campana')->after('empresa_id');
            $table->unsignedBigInteger('mail_template_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->dropColumn('tipo');
            // Solo revertir si no hay registros con mail_template_id null
            $table->unsignedBigInteger('mail_template_id')->nullable(false)->change();
        });
    }
};
