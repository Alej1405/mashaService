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
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('forma_pago', [
                'efectivo',
                'transferencia', 
                'cheque',
                'tarjeta',
                'credito'
            ])->default('efectivo')->after('tipo_venta');
            
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('forma_pago')
                ->constrained('bank_accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['forma_pago', 'bank_account_id']);
        });
    }
};
