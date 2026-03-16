<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'cash_register_id')) {
                $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('sales', 'credit_card_id')) {
                $table->foreignId('credit_card_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_register_id']);
            $table->dropForeign(['credit_card_id']);
            $table->dropColumn(['cash_register_id', 'credit_card_id']);
        });
    }
};
