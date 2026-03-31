<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventory_items', 'presentation_id')) {
            return;
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('presentation_id')
                ->nullable()
                ->after('account_plan_id')
                ->constrained('item_presentations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['presentation_id']);
            $table->dropColumn('presentation_id');
        });
    }
};
