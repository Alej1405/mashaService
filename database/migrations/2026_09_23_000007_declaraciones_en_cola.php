<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Declaraciones generadas en segundo plano.
 *
 * Pedir una declaración no devuelve el archivo: devuelve un número de
 * seguimiento. El ERP calcula, el microservicio arma el XML y, cuando termina,
 * el usuario ve el aviso. Un informe anual de doce meses no puede colgar de una
 * petición HTTP.
 *
 * Los casilleros calculados se guardan en `datos`: la declaración del mes
 * siguiente arrastra de ahí sus saldos (483, 605, 606, 607, 608) sin recalcular
 * el mes anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('declaraciones')) {
            Schema::create('declaraciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->uuid('seguimiento')->unique();     // lo que ve el usuario
                $table->string('tipo', 20);                // f104 · ats · f103
                $table->unsignedSmallInteger('anio');
                $table->unsignedTinyInteger('mes')->nullable();  // nulo en los anuales
                $table->string('estado', 20)->default('pendiente'); // pendiente|procesando|listo|error
                $table->json('datos')->nullable();         // los casilleros ya calculados
                $table->json('avisos')->nullable();        // lo que el contador debe revisar
                $table->string('archivo', 255)->nullable();
                $table->text('mensaje')->nullable();       // el error, cuando lo hay
                $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('generado_en')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'tipo', 'anio', 'mes']);
                $table->index(['empresa_id', 'estado']);
            });
        }

        // La campana del panel: sin esta tabla el aviso no tiene dónde guardarse.
        //
        // `data` va como json, no como text: Filament filtra sus avisos con
        // data->>'format' = 'filament', y en Postgres ese operador no existe
        // sobre text. La migración que trae Laravel usa text porque está
        // pensada para MySQL.
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        } elseif (DB::getDriverName() === 'pgsql') {
            // Si la tabla ya existía con text, se convierte sin perder avisos.
            DB::statement("ALTER TABLE notifications ALTER COLUMN data TYPE json USING data::json");
        }

        // El ERP guarda 'ruc' y 'cedula'; el ATS espera un código distinto según
        // si el documento va en la sección de compras o en la de ventas.
        $reglas = [
            ['tipo_identificacion', 'compra:ruc',              'ats', '2', '01'],
            ['tipo_identificacion', 'compra:cedula',           'ats', '2', '02'],
            ['tipo_identificacion', 'compra:pasaporte',        'ats', '2', '03'],
            ['tipo_identificacion', 'venta:ruc',               'ats', '2', '04'],
            ['tipo_identificacion', 'venta:cedula',            'ats', '2', '05'],
            ['tipo_identificacion', 'venta:pasaporte',         'ats', '2', '06'],
            ['tipo_identificacion', 'venta:consumidor_final',  'ats', '2', '07'],
            // Tipo de identificación del proveedor · tabla 14
            ['tipo_persona', 'natural',  'ats', '14', '01'],
            ['tipo_persona', 'juridica', 'ats', '14', '02'],
            // Tipo de pago · tabla 15
            ['tipo_pago', 'residente',    'ats', '15', '01'],
            ['tipo_pago', 'no_residente', 'ats', '15', '02'],
        ];

        DB::table('mapa_sri')->whereNull('empresa_id')
            ->whereIn('entidad', ['tipo_identificacion', 'tipo_persona', 'tipo_pago'])->delete();

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
        Schema::dropIfExists('declaraciones');
    }
};
