<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tres cosas que le faltaban al módulo para poder decir "estamos al día".
 *
 * 1. El inicio de gestión en el ERP. No es la fecha en que nació la empresa:
 *    es desde cuándo el sistema responde por sus declaraciones. Sin esa raya
 *    en el suelo, no se puede afirmar que falta un informe, porque no se sabe
 *    desde cuándo debía existir.
 *
 * 2. La huella de cada informe: cuándo se generó, quién lo descargó y cuándo
 *    se presentó. Un informe generado y nunca descargado no es cumplimiento.
 *
 * 3. Los comprobantes que la empresa se baja del portal del SRI, para los
 *    meses en los que el movimiento no está en el sistema, y el certificado
 *    de firma electrónica con su vigencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1 · desde cuándo responde el ERP ────────────────────────────────
        Schema::table('empresas', function (Blueprint $table) {
            if (! Schema::hasColumn('empresas', 'inicio_gestion_erp')) {
                $table->date('inicio_gestion_erp')->nullable()->after('agente_retencion');
            }

            if (! Schema::hasColumn('empresas', 'periodicidad_iva')) {
                // mensual, o semestral para el régimen RIMPE
                $table->string('periodicidad_iva', 12)->default('mensual')->after('inicio_gestion_erp');
            }
        });

        // ── 2 · la huella del informe ───────────────────────────────────────
        Schema::table('declaraciones', function (Blueprint $table) {
            foreach ([
                'descargado_en'  => fn () => $table->timestamp('descargado_en')->nullable(),
                'presentado_en'  => fn () => $table->date('presentado_en')->nullable(),
                'comprobante_presentacion' => fn () => $table->string('comprobante_presentacion', 40)->nullable(),
                'valor_pagado'   => fn () => $table->decimal('valor_pagado', 15, 2)->nullable(),
            ] as $columna => $crear) {
                if (! Schema::hasColumn('declaraciones', $columna)) {
                    $crear();
                }
            }
        });

        if (! Schema::hasColumn('declaraciones', 'descargado_por')) {
            Schema::table('declaraciones', function (Blueprint $table) {
                $table->foreignId('descargado_por')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        // ── 3 · los comprobantes bajados del portal del SRI ─────────────────
        if (! Schema::hasTable('comprobantes_sri')) {
            Schema::create('comprobantes_sri', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                // La clave de acceso son 49 dígitos y es única en todo el país:
                // es la que impide contar dos veces la misma factura.
                $table->string('clave_acceso', 49)->nullable();
                $table->string('origen', 12);              // recibido | emitido
                $table->string('tipo_comprobante', 3);     // tabla 4 del ATS
                $table->string('identificacion', 20);
                $table->string('razon_social', 200)->nullable();
                $table->date('fecha_emision');
                $table->string('numero', 30)->nullable();
                $table->string('establecimiento', 3)->nullable();
                $table->string('punto_emision', 3)->nullable();
                $table->string('secuencial', 9)->nullable();
                $table->decimal('base_gravada', 15, 2)->default(0);
                $table->decimal('base_cero', 15, 2)->default(0);
                $table->decimal('iva', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                // declarado = el SRI publicó la base; estimado = se despejó del total
                $table->string('desglose', 12)->default('declarado');
                // con qué documento del ERP casa, si casa con alguno
                $table->string('conciliado_con', 20)->nullable();   // purchase | gasto | sale
                $table->unsignedBigInteger('conciliado_id')->nullable();
                $table->timestamp('importado_en')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'origen', 'fecha_emision']);
                $table->index(['empresa_id', 'conciliado_con', 'conciliado_id']);
            });

            // Una clave de acceso no se importa dos veces para la misma empresa.
            DB::statement('CREATE UNIQUE INDEX comprobantes_sri_clave ON comprobantes_sri (empresa_id, clave_acceso) WHERE clave_acceso IS NOT NULL');
        }

        // ── 4 · el certificado de firma electrónica ─────────────────────────
        if (! Schema::hasTable('firmas_electronicas')) {
            Schema::create('firmas_electronicas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->string('archivo', 255);            // ruta en disco privado
                $table->text('clave');                     // cifrada con la APP_KEY
                $table->string('titular', 200)->nullable();
                $table->string('identificacion', 20)->nullable();
                $table->string('emisor', 120)->nullable(); // Security Data, Uanataca, BCE…
                $table->string('numero_serie', 80)->nullable();
                $table->date('valido_desde')->nullable();
                $table->date('valido_hasta')->nullable();
                $table->boolean('activa')->default(true);
                $table->foreignId('cargada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['empresa_id', 'activa']);
            });
        }

        // El inicio de gestión de quien ya venía usando el ERP: su primer
        // asiento confirmado. Mejor eso que una fecha inventada.
        foreach (DB::table('empresas')->whereNull('inicio_gestion_erp')->pluck('id') as $empresaId) {
            $primera = DB::table('journal_entries')->where('empresa_id', $empresaId)
                ->where('status', 'confirmado')->min('fecha');

            if ($primera) {
                DB::table('empresas')->where('id', $empresaId)
                    ->update(['inicio_gestion_erp' => date('Y-m-01', strtotime($primera))]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('firmas_electronicas');
        Schema::dropIfExists('comprobantes_sri');
    }
};
