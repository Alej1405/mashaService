<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una compra personal no se registra como gasto: solo cuenta su IVA. El tipo
 * "Gastos no deducibles" que se creó para ella queda sin uso y se quita, salvo
 * que algo ya lo use.
 */
return new class extends Migration
{
    public function up(): void
    {
        $id = DB::table('tipos_gasto')->where('codigo', 'gastos-no-deducibles')->whereNull('empresa_id')->value('id');

        if (! $id) {
            return;
        }

        foreach (['gasto_lineas', 'comprobantes_sri', 'productos_proveedor', 'purchase_items'] as $tabla) {
            if (Schema::hasColumn($tabla, 'tipo_gasto_id') && DB::table($tabla)->where('tipo_gasto_id', $id)->exists()) {
                return;
            }
        }

        DB::table('tipos_gasto')->where('id', $id)->delete();
    }

    public function down(): void
    {
    }
};
