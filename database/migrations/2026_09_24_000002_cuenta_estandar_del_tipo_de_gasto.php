<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los tipos de gasto saben a qué cuenta van, sin depender de la empresa.
 *
 * Los catorce tipos son globales y su `account_plan_id` estaba vacío, porque
 * cada empresa tiene su propio plan y no hay un id que sirva para todas. El
 * resultado era que ningún gasto podía generar asiento.
 *
 * La solución es la misma que con el catálogo de la Superintendencia: se
 * guarda el **código** del plan estándar, que sí es igual en todas, y al
 * confirmar se resuelve la cuenta de esa empresa.
 *
 * Marco: skill `contabilidad-ec` — el plan de cuentas es estándar.
 */
return new class extends Migration
{
    /** Tipo de gasto → cuenta del plan estándar del ERP. */
    private const CUENTAS = [
        'Alimentación'                          => '6.1.16',
        'Transporte y movilización'             => '6.1.09',
        'Combustible'                            => '6.1.07',
        'Servicios básicos'                     => '6.1.14',
        'Arriendo de inmuebles'                  => '6.1.05',
        'Honorarios profesionales'               => '6.1.04',
        'Servicios profesionales de sociedades'  => '6.1.04',
        'Publicidad y comunicación'             => '6.1.10',
        'Suministros de oficina'                 => '6.1.08',
        'Mantenimiento y reparaciones'           => '6.1.06',
        'Seguros'                                => '6.1.11',
        'Capacitación'                          => '6.1.16',
        'Gastos bancarios y financieros'         => '6.2.02',
        'Impuestos, tasas y contribuciones'      => '6.1.15',
    ];

    public function up(): void
    {
        Schema::table('tipos_gasto', function (Blueprint $table) {
            if (! Schema::hasColumn('tipos_gasto', 'codigo_cuenta')) {
                $table->string('codigo_cuenta', 20)->nullable()->after('account_plan_id');
            }
        });

        foreach (self::CUENTAS as $nombre => $codigo) {
            DB::table('tipos_gasto')->where('nombre', $nombre)->update(['codigo_cuenta' => $codigo]);
        }

        // Los que ya tienen empresa propia se resuelven de una vez.
        foreach (DB::table('tipos_gasto')->whereNotNull('empresa_id')->whereNull('account_plan_id')->get() as $t) {
            if (! $t->codigo_cuenta) {
                continue;
            }

            $cuenta = DB::table('account_plans')->where('empresa_id', $t->empresa_id)
                ->where('code', $t->codigo_cuenta)->value('id');

            if ($cuenta) {
                DB::table('tipos_gasto')->where('id', $t->id)->update(['account_plan_id' => $cuenta]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tipos_gasto', function (Blueprint $table) {
            $table->dropColumn('codigo_cuenta');
        });
    }
};
