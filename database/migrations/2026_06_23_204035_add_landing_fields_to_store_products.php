<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->string('unidad_precio', 80)->nullable()->after('precio_venta');
            $table->json('caracteristicas')->nullable()->after('galeria');
            $table->string('meta_titulo', 200)->nullable()->after('caracteristicas');
            $table->text('meta_descripcion')->nullable()->after('meta_titulo');
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropColumn(['unidad_precio', 'caracteristicas', 'meta_titulo', 'meta_descripcion']);
        });
    }
};
