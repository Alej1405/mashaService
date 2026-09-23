<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La ficha del RUC dentro de la empresa.
 *
 * El certificado del SRI trae lo que el ERP necesitaba y no tenía: régimen,
 * representante legal, si está obligada a llevar contabilidad, si es agente de
 * retención y desde cuándo opera. Se guarda el PDF como respaldo y los campos
 * quedan editables: el documento precarga, la persona confirma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('ruc_pdf_path')->nullable()->after('logo_path');
            $table->timestamp('ruc_leido_en')->nullable()->after('ruc_pdf_path');
            $table->string('representante_legal', 160)->nullable()->after('ruc_leido_en');
            $table->string('regimen', 30)->nullable()->after('representante_legal');
            $table->string('estado_ruc', 20)->nullable()->after('regimen');
            $table->boolean('obligado_contabilidad')->default(true)->after('estado_ruc');
            $table->boolean('contribuyente_especial')->default(false)->after('obligado_contabilidad');
            $table->string('jurisdiccion', 120)->nullable()->after('contribuyente_especial');
            $table->string('provincia', 60)->nullable()->after('jurisdiccion');
            $table->string('canton', 60)->nullable()->after('provincia');
            $table->string('parroquia', 60)->nullable()->after('canton');
            $table->json('actividades_economicas')->nullable()->after('parroquia');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'ruc_pdf_path', 'ruc_leido_en', 'representante_legal', 'regimen', 'estado_ruc',
                'obligado_contabilidad', 'contribuyente_especial', 'jurisdiccion',
                'provincia', 'canton', 'parroquia', 'actividades_economicas',
            ]);
        });
    }
};
