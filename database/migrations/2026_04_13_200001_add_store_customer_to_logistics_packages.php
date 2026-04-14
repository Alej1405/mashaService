<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->foreignId('store_customer_id')
                ->nullable()
                ->after('consignatario_id')
                ->constrained('store_customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropForeign(['store_customer_id']);
            $table->dropColumn('store_customer_id');
        });
    }
};
