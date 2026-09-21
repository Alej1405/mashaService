<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El Laboratorio usa los posts que ya existen en vez de una tabla nueva.
 * Solo agrega columnas opcionales: ninguna empresa que ya publica se entera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            $table->text('resumen')->nullable()->after('slug');
            $table->string('serie', 80)->nullable()->after('resumen');
            $table->unsignedSmallInteger('minutos_lectura')->nullable()->after('serie');
            $table->boolean('destacado')->default(false)->after('minutos_lectura');
        });
    }

    public function down(): void
    {
        Schema::table('cms_posts', function (Blueprint $table) {
            $table->dropColumn(['resumen', 'serie', 'minutos_lectura', 'destacado']);
        });
    }
};
