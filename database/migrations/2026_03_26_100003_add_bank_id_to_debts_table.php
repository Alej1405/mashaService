<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->foreignId('bank_id')
                ->nullable()
                ->after('credit_card_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');
        });
    }
};
