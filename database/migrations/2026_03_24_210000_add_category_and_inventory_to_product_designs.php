<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            if (!Schema::hasColumn('product_designs', 'store_category_id')) {
                $table->foreignId('store_category_id')
                    ->nullable()
                    ->constrained('store_categories')
                    ->nullOnDelete()
                    ->after('categoria');
            }
            if (!Schema::hasColumn('product_designs', 'inventory_item_id')) {
                $table->foreignId('inventory_item_id')
                    ->nullable()
                    ->constrained('inventory_items')
                    ->nullOnDelete()
                    ->after('store_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->dropConstrainedForeignId('store_category_id');
        });
    }
};
