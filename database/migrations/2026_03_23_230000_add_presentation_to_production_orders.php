<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('product_presentation_id')
                ->nullable()
                ->after('inventory_item_id')
                ->constrained('product_presentations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\ProductPresentation::class);
            $table->dropColumn('product_presentation_id');
        });
    }
};
