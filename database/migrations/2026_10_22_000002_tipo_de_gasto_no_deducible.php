<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Una compra personal hecha con el RUC de la empresa, cuando el 104 de ese mes ya
 * la declaró: entra como gasto no deducible con su IVA. Va a "Gastos no deducibles"
 * y no retiene: no es un gasto de la empresa.
 */
return new class extends Migration
{
    private const CODIGO = 'gastos-no-deducibles';

    public function up(): void
    {
        DB::table('tipos_gasto')->updateOrInsert(['codigo' => self::CODIGO, 'empresa_id' => null], [
            'nombre' => 'Gastos no deducibles',
            'codigo_cuenta' => '6.3.02',
            'concepto_retencion' => null,
            'deducible' => false,
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
