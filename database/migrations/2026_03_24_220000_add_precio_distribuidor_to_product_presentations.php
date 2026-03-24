<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_presentations', function (Blueprint $table) {
            if (!Schema::hasColumn('product_presentations', 'precio_distribuidor')) {
                $table->decimal('precio_distribuidor', 15, 4)->default(0)->after('pvp_estimado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_presentations', function (Blueprint $table) {
            $table->dropColumn('precio_distribuidor');
        });
    }
};
