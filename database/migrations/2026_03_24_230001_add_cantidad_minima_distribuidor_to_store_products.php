<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            if (!Schema::hasColumn('store_products', 'cantidad_minima_distribuidor')) {
                $table->unsignedSmallInteger('cantidad_minima_distribuidor')->default(10)->after('precio_distribuidor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropColumn('cantidad_minima_distribuidor');
        });
    }
};
