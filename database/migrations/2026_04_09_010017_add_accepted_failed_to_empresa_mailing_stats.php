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
        Schema::table('empresa_mailing_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('accepted')->default(0)->after('empresa_id');
            $table->unsignedBigInteger('failed')->default(0)->after('delivered');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_mailing_stats', function (Blueprint $table) {
            $table->dropColumn(['accepted', 'failed']);
        });
    }
};
