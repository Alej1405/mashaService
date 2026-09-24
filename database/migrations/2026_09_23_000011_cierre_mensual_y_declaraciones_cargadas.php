<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El mes declarado se cierra, y las declaraciones de antes del ERP caben aquí.
 *
 * Un mes presentado que se puede recalcular va a dar distinto la próxima vez, y
 * entonces lo que consta en el SRI y lo que dice el sistema dejan de cuadrar.
 * Al marcar un período como presentado se cierra: después no entran movimientos
 * con esa fecha. Corregirlo no es reabrirlo, es una declaración sustitutiva.
 *
 * Y las declaraciones que la empresa presentó antes de usar el ERP son
 * declaraciones: van en la misma tabla, con su origen y su PDF. Un dato vive en
 * un solo sitio, así que no hay tabla de "históricas" aparte.
 *
 * Marco: skill `contabilidad-ec` — cierre mensual, y un dato un sitio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            if (! Schema::hasColumn('declaraciones', 'origen')) {
                // sistema = la calculó el ERP · cargada = la presentó antes y se sube
                $table->string('origen', 12)->default('sistema')->after('tipo');
            }

            if (! Schema::hasColumn('declaraciones', 'archivo_pdf')) {
                $table->string('archivo_pdf', 255)->nullable()->after('archivo');
            }

            if (! Schema::hasColumn('declaraciones', 'sustituye_a')) {
                $table->unsignedBigInteger('sustituye_a')->nullable()->after('comprobante_presentacion');
            }
        });

        // El período cerrado sabe por qué se cerró.
        Schema::table('periodos_contables', function (Blueprint $table) {
            if (! Schema::hasColumn('periodos_contables', 'declaracion_id')) {
                $table->foreignId('declaracion_id')->nullable()->after('mes')
                    ->constrained('declaraciones')->nullOnDelete();
            }

            if (! Schema::hasColumn('periodos_contables', 'motivo')) {
                // inventario = lo cerró bodega · iva = se presentó el 104
                $table->string('motivo', 20)->default('inventario')->after('declaracion_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropColumn(['origen', 'archivo_pdf', 'sustituye_a']);
        });
    }
};
