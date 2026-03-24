<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            if (!Schema::hasColumn('store_products', 'product_design_id')) {
                $table->foreignId('product_design_id')
                    ->nullable()
                    ->constrained('product_designs')
                    ->nullOnDelete()
                    ->after('empresa_id');
            }
            if (!Schema::hasColumn('store_products', 'product_presentation_id')) {
                $table->foreignId('product_presentation_id')
                    ->nullable()
                    ->constrained('product_presentations')
                    ->nullOnDelete()
                    ->after('product_design_id');
            }
            // inventory_item_id ya es nullable en la tabla; solo lo dejamos opcional en el form
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_presentation_id');
            $table->dropConstrainedForeignId('product_design_id');
        });
    }
};
