<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos del SRI y el mapa que traduce el ERP a cada anexo.
 *
 * Cada anexo numera sus tablas desde 1 y los códigos chocan: la tabla 2 del ATS
 * es "tipo de identificación" con códigos 01..21, la tabla 2 del REBEFICS es
 * "tipo de identificación" con C/P/R/E, y la tabla 1 del anexo de dividendos es
 * otra vez tipo de identificación. Por eso la clave aquí no es el código: es
 * anexo + tabla + código + fecha de vigencia.
 *
 * `mapa_sri` es la única puerta entre el ERP y esos códigos. Nada de ir
 * agregando una columna por anexo a cada tabla del sistema: una fila dice que
 * tal forma de pago del ERP es el código 20 de la tabla 13 del ATS, y otra que
 * ese mismo pago no le importa al 104.
 */
return new class extends Migration
{
    /** Los cuatro archivos oficiales ya convertidos a JSON. */
    private const ARCHIVOS = ['ats', 'rebefics', 'adi', 'f104'];

    public function up(): void
    {
        if (! Schema::hasTable('catalogos_sri')) {
            Schema::create('catalogos_sri', function (Blueprint $table) {
                $table->id();
                $table->string('anexo', 20);              // ats · rebefics · adi · f104
                $table->string('tabla', 20);              // '1', '3.10', 'ventas'…
                $table->string('nombre_tabla', 160)->nullable();
                $table->string('codigo', 16);
                $table->text('descripcion')->nullable();
                // texto, no número: el SRI publica "1 a 2", "2/mil", "varios porcentajes"
                $table->string('porcentaje', 200)->nullable();
                $table->string('grupo', 20)->nullable();  // residente | no_residente
                $table->text('compatible_con')->nullable(); // códigos admitidos de otra tabla
                $table->string('formula', 200)->nullable(); // solo en formularios
                // sin nulos: un código sin vigencia publicada rige desde siempre,
                // y el índice único no puede llevar columnas nulas
                $table->date('vigente_desde')->default('1900-01-01');
                $table->date('vigente_hasta')->nullable();
                $table->timestamps();

                $table->unique(['anexo', 'tabla', 'codigo', 'vigente_desde'], 'catalogos_sri_clave');
                $table->index(['anexo', 'tabla']);
            });
        }

        if (! Schema::hasTable('mapa_sri')) {
            Schema::create('mapa_sri', function (Blueprint $table) {
                $table->id();
                // nulo = regla del sistema, igual para todas las empresas
                $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
                $table->string('entidad', 40);   // forma_pago, tipo_documento, tipo_gasto…
                $table->string('clave', 80);     // el valor del ERP, o el id del modelo
                $table->string('anexo', 20);
                $table->string('tabla', 20);
                $table->string('codigo', 16);
                $table->timestamps();

                $table->index(['entidad', 'anexo']);
            });

            // Dos índices parciales en vez de uno: una regla del sistema lleva
            // empresa_id nulo, y en un único normal los nulos no chocan entre sí.
            DB::statement('CREATE UNIQUE INDEX mapa_sri_sistema ON mapa_sri (entidad, clave, anexo, tabla) WHERE empresa_id IS NULL');
            DB::statement('CREATE UNIQUE INDEX mapa_sri_empresa ON mapa_sri (empresa_id, entidad, clave, anexo, tabla) WHERE empresa_id IS NOT NULL');
        }

        $this->sembrarCatalogos();
        $this->sembrarMapa();
    }

    /** Carga los JSON oficiales. Se puede volver a correr: actualiza en lugar de duplicar. */
    private function sembrarCatalogos(): void
    {
        foreach (self::ARCHIVOS as $anexo) {
            $archivo = database_path("data/sri/{$anexo}.json");

            if (! file_exists($archivo)) {
                continue;
            }

            $filas = json_decode(file_get_contents($archivo), true) ?: [];

            foreach (array_chunk($filas, 400) as $lote) {
                DB::table('catalogos_sri')->upsert(
                    array_map(fn (array $f) => [
                        'anexo'          => $f['anexo'],
                        'tabla'          => $f['tabla'],
                        'nombre_tabla'   => $f['nombre_tabla'] ?? null,
                        'codigo'         => $f['codigo'],
                        'descripcion'    => $f['descripcion'] ?? null,
                        'porcentaje'     => isset($f['porcentaje']) ? mb_substr((string) $f['porcentaje'], 0, 200) : null,
                        'grupo'          => $f['grupo'] ?? null,
                        'compatible_con' => $f['compatible_con'] ?? ($f['sustentos'] ?? ($f['comprobantes'] ?? null)),
                        'formula'        => $f['formula'] ?? null,
                        'vigente_desde'  => $f['desde'] ?? '1900-01-01',
                        'vigente_hasta'  => $f['hasta'] ?? null,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ], $lote),
                    ['anexo', 'tabla', 'codigo', 'vigente_desde'],
                    ['nombre_tabla', 'descripcion', 'porcentaje', 'grupo', 'compatible_con', 'formula', 'vigente_hasta', 'updated_at'],
                );
            }
        }
    }

    /**
     * El mapa de arranque: lo que el ERP ya guarda como texto y el SRI espera
     * como código. Son reglas del sistema, iguales para toda empresa.
     */
    private function sembrarMapa(): void
    {
        $reglas = [
            // Forma de pago del gasto → tabla 13 del ATS
            ['forma_pago', 'efectivo',      'ats', '13', '01'],
            ['forma_pago', 'transferencia', 'ats', '13', '20'],
            ['forma_pago', 'cheque',        'ats', '13', '20'],
            ['forma_pago', 'tarjeta',       'ats', '13', '19'],
            ['forma_pago', 'credito',       'ats', '13', '01'],

            // Tipo de documento del gasto → tabla 4 del ATS
            ['tipo_documento', 'factura',            'ats', '4', '1'],
            ['tipo_documento', 'nota_venta',         'ats', '4', '2'],
            ['tipo_documento', 'liquidacion_compra', 'ats', '4', '3'],
            ['tipo_documento', 'nota_credito',       'ats', '4', '4'],
            ['tipo_documento', 'nota_debito',        'ats', '4', '5'],

            // Sustento tributario según a qué va el gasto → tabla 5 del ATS
            ['sustento', 'servicio',    'ats', '5', '01'],
            ['sustento', 'inventario',  'ats', '5', '06'],
            ['sustento', 'activo_fijo', 'ats', '5', '03'],
            ['sustento', 'viaje',       'ats', '5', '05'],

            // Porcentaje de retención de IVA → casillero del 104 y tabla 11 del ATS
            ['retencion_iva', '10',  'f104', 'retencion', '721'],
            ['retencion_iva', '20',  'f104', 'retencion', '723'],
            ['retencion_iva', '30',  'f104', 'retencion', '725'],
            ['retencion_iva', '50',  'f104', 'retencion', '727'],
            ['retencion_iva', '70',  'f104', 'retencion', '729'],
            ['retencion_iva', '100', 'f104', 'retencion', '731'],
            ['retencion_iva', '10',  'ats', '11', '9'],
            ['retencion_iva', '20',  'ats', '11', '10'],
            ['retencion_iva', '30',  'ats', '11', '1'],
            ['retencion_iva', '50',  'ats', '11', '11'],
            ['retencion_iva', '70',  'ats', '11', '2'],
            ['retencion_iva', '100', 'ats', '11', '3'],

            // Concepto de retención de renta del ERP → código de la tabla 3.10
            ['retencion_renta', 'Transferencia de bienes muebles de naturaleza corporal',   'ats', '3.10', '312'],
            ['retencion_renta', 'Servicios con predominio de mano de obra',                 'ats', '3.10', '307'],
            ['retencion_renta', 'Servicios con predominio del intelecto',                   'ats', '3.10', '304'],
            ['retencion_renta', 'Honorarios profesionales',                                 'ats', '3.10', '303'],
            ['retencion_renta', 'Servicios profesionales prestados por sociedades',         'ats', '3.10', '303A'],
            ['retencion_renta', 'Publicidad y comunicación',                                'ats', '3.10', '309'],
            ['retencion_renta', 'Arrendamiento de inmuebles',                               'ats', '3.10', '320'],
            ['retencion_renta', 'Seguros y reaseguros',                                     'ats', '3.10', '322'],
            ['retencion_renta', 'Otras retenciones aplicables el 1%',                       'ats', '3.10', '343'],
            ['retencion_renta', 'Otras retenciones aplicables el 3%',                       'ats', '3.10', '3440'],
            ['retencion_renta', 'Sin retención',                                            'ats', '3.10', '332'],
        ];

        // Las del sistema se rehacen enteras; las que cada empresa haya ajustado
        // llevan su empresa_id y no se tocan.
        DB::table('mapa_sri')->whereNull('empresa_id')->delete();

        DB::table('mapa_sri')->insert(array_map(fn (array $r) => [
            'empresa_id' => null,
            'entidad'    => $r[0],
            'clave'      => $r[1],
            'anexo'      => $r[2],
            'tabla'      => $r[3],
            'codigo'     => $r[4],
            'created_at' => now(),
            'updated_at' => now(),
        ], $reglas));
    }

    public function down(): void
    {
        Schema::dropIfExists('mapa_sri');
        Schema::dropIfExists('catalogos_sri');
    }
};
