<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos para que cada categoría pueda generar su propia landing en el frontend:
 * SEO, banner de cabecera y contenido extendido. Solo presentación (tienda),
 * sin relación con contabilidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            $table->string('meta_titulo')->nullable()->after('descripcion');
            $table->text('meta_descripcion')->nullable()->after('meta_titulo');
            $table->string('banner')->nullable()->after('imagen');
            $table->text('contenido')->nullable()->after('banner');
            $table->boolean('destacada')->default(false)->after('publicado');
        });
    }

    public function down(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            $table->dropColumn(['meta_titulo', 'meta_descripcion', 'banner', 'contenido', 'destacada']);
        });
    }
};
