<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vincular portal ↔ ERP: un StoreCustomer puede tener su Customer ERP equivalente
        Schema::table('store_customers', function (Blueprint $table) {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('customers')
                ->nullOnDelete();
        });

        // El paquete logístico necesita saber a qué Customer ERP facturarle
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('store_customer_id')
                ->constrained('customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('store_customers', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
