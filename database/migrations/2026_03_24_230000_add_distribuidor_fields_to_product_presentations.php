<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_presentations', function (Blueprint $table) {
            if (!Schema::hasColumn('product_presentations', 'margen_distribuidor')) {
                $table->decimal('margen_distribuidor', 5, 2)->default(40)->after('precio_distribuidor');
            }
            if (!Schema::hasColumn('product_presentations', 'cantidad_minima_distribuidor')) {
                $table->unsignedSmallInteger('cantidad_minima_distribuidor')->default(10)->after('margen_distribuidor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_presentations', function (Blueprint $table) {
            $table->dropColumn(['margen_distribuidor', 'cantidad_minima_distribuidor']);
        });
    }
};
