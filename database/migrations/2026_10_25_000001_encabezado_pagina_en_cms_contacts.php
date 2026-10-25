<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El encabezado de la página de contacto del sitio (título y texto de
 * introducción) se edita desde el CMS, junto al resto de datos de contacto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_contacts', function (Blueprint $table) {
            $table->string('pagina_titulo', 200)->nullable()->after('empresa_id');
            $table->text('pagina_descripcion')->nullable()->after('pagina_titulo');
        });
    }

    public function down(): void
    {
        Schema::table('cms_contacts', function (Blueprint $table) {
            $table->dropColumn(['pagina_titulo', 'pagina_descripcion']);
        });
    }
};
