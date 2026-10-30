<?php

use App\Services\CatalogoSriService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que el REBEFICS pide del beneficiario final y el ERP no guardaba.
 *
 * El socio persona natural con participación es beneficiario final por propiedad:
 * el SRI pide su fecha de nacimiento, su nacionalidad y residencia fiscal (código
 * de país) y su domicilio con los códigos de provincia, cantón y parroquia de sus
 * Tablas ADM.
 *
 * Y el catálogo se recarga: trae las Tablas ADM y corrige la tabla 10 (figura
 * jurídica), que se había cargado con los países en lugar de las figuras.
 *
 * Marco: skill `declaraciones-sri-ec` y la ficha técnica REBEFICS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable()->after('nacionalidad');
            $table->string('pais_nacionalidad', 3)->default('593')->after('fecha_nacimiento');
            $table->string('residencia_fiscal', 3)->default('593')->after('pais_nacionalidad');
            $table->string('provincia', 3)->nullable()->after('residencia_fiscal');
            $table->string('canton', 5)->nullable()->after('provincia');
            $table->string('parroquia', 7)->nullable()->after('canton');
            $table->string('calle', 100)->nullable()->after('parroquia');
            $table->string('numero', 10)->nullable()->after('calle');
            $table->string('interseccion', 100)->nullable()->after('numero');
            $table->string('codigo_postal', 10)->nullable()->after('interseccion');
            $table->string('referencia', 100)->nullable()->after('codigo_postal');
        });

        app(CatalogoSriService::class)->cargar('rebefics', reemplazar: ['10']);
    }

    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->dropColumn(['fecha_nacimiento', 'pais_nacionalidad', 'residencia_fiscal', 'provincia', 'canton',
                'parroquia', 'calle', 'numero', 'interseccion', 'codigo_postal', 'referencia']);
        });
    }
};
