<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('referencia_id')->nullable()->after('mailing_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->dropColumn('referencia_id');
        });
    }
};
