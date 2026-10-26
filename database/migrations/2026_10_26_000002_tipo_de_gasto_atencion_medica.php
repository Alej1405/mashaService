<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La atención médica que la empresa cubre a su personal es un beneficio social y un
 * gasto deducible: va a "Beneficios sociales" (6.1.02).
 */
return new class extends Migration
{
    private const CODIGO = 'atencion-medica-al-personal';

    public function up(): void
    {
        DB::table('tipos_gasto')->updateOrInsert(['codigo' => self::CODIGO, 'empresa_id' => null], [
            'nombre' => 'Atención médica al personal',
            'codigo_cuenta' => '6.1.02',
            'concepto_retencion' => null,
            'deducible' => true,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('tipos_gasto')->where('codigo', self::CODIGO)->whereNull('empresa_id')->delete();
    }
};
