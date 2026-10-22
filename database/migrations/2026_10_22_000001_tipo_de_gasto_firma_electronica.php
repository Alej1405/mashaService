<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La firma electrónica (Security Data y similares) no es un servicio profesional:
 * es un certificado que se renueva cada año. Va a "Otros gastos de administración
 * y ventas" y no retiene como servicio profesional de sociedades.
 */
return new class extends Migration
{
    private const CODIGO = 'firma-electronica-y-certificados-digitales';

    public function up(): void
    {
        DB::table('tipos_gasto')->updateOrInsert(['codigo' => self::CODIGO, 'empresa_id' => null], [
            'nombre' => 'Firma electrónica y certificados digitales',
            'codigo_cuenta' => '6.1.19',
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
