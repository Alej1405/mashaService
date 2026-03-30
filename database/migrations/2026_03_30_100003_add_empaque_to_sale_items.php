<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('item_presentation_id')->nullable()->after('inventory_item_id')
                  ->constrained('item_presentations')->nullOnDelete();
            $table->decimal('factor_empaque', 15, 6)->default(1)->after('item_presentation_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['item_presentation_id']);
            $table->dropColumn(['item_presentation_id', 'factor_empaque']);
        });
    }
};
