<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'forma_pago')) {
                $table->enum('forma_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'credito'])->default('efectivo');
            }
            if (!Schema::hasColumn('purchases', 'cash_register_id')) {
                $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('purchases', 'credit_card_id')) {
                $table->foreignId('credit_card_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('purchases', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['cash_register_id']);
            $table->dropForeign(['credit_card_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['forma_pago', 'cash_register_id', 'credit_card_id', 'bank_account_id']);
        });
    }
};
