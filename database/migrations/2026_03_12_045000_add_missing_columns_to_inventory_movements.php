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
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'unit_price')) {
                $table->decimal('unit_price', 15, 4)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('inventory_movements', 'total')) {
                $table->decimal('total', 15, 4)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('inventory_movements', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total', 'notes']);
        });
    }
};
