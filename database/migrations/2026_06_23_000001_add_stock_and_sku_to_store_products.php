<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('slug');
            $table->unsignedInteger('stock')->default(0)->after('precio_venta');
            $table->unsignedInteger('stock_minimo')->default(5)->after('stock');
            $table->boolean('gestionar_stock')->default(true)->after('stock_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropColumn(['sku', 'stock', 'stock_minimo', 'gestionar_stock']);
        });
    }
};
