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
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'total') && !Schema::hasColumn('purchase_items', 'total_item')) {
                $table->renameColumn('total', 'total_item');
            } elseif (!Schema::hasColumn('purchase_items', 'total_item')) {
                $table->decimal('total_item', 15, 4)->default(0)->after('iva_monto');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'total_item')) {
                $table->renameColumn('total_item', 'total');
            }
        });
    }
};
