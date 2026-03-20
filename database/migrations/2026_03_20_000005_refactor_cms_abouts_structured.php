<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_abouts', function (Blueprint $table) {
            $table->dropColumn('cuerpo');
            $table->text('descripcion')->nullable()->after('titulo');
            $table->json('por_que_nosotros')->nullable()->after('descripcion'); // [{ "texto": "..." }]
            $table->json('numeros')->nullable()->after('por_que_nosotros');     // [{ "valor": "12+", "etiqueta": "años" }]
            $table->json('caracteristicas')->nullable()->after('numeros');      // [{ "titulo": "...", "descripcion": "..." }]
        });
    }

    public function down(): void
    {
        Schema::table('cms_abouts', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'por_que_nosotros', 'numeros', 'caracteristicas']);
            $table->longText('cuerpo')->nullable()->after('titulo');
        });
    }
};
