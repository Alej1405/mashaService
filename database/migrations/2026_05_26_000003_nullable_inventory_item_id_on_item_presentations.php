<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La columna solo existe en bases que vienen de MariaDB; una instalación nueva no la crea.
        if (! Schema::hasColumn('item_presentations', 'inventory_item_id')) {
            return;
        }

        Schema::table('item_presentations', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('item_presentations', 'inventory_item_id')) {
            return;
        }

        Schema::table('item_presentations', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable(false)->change();
        });
    }
};
