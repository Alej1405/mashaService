<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->boolean('publicado_catalogo')->default(false)->after('activo');
        });
        Schema::table('service_designs', function (Blueprint $table) {
            $table->boolean('publicado_catalogo')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropColumn('publicado_catalogo');
        });
        Schema::table('service_designs', function (Blueprint $table) {
            $table->dropColumn('publicado_catalogo');
        });
    }
};
