<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            if (!Schema::hasColumn('store_products', 'precio_distribuidor')) {
                $table->decimal('precio_distribuidor', 10, 4)->default(0)->after('precio_venta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropColumn('precio_distribuidor');
        });
    }
};
