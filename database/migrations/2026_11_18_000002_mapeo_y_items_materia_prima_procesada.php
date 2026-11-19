<?php

use App\Models\AccountPlan;
use App\Models\AccountingMap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cimiento contable de la materia prima procesada (semielaborado).
 *
 *  1. Mapea el tipo `materia_prima_procesada` a la cuenta 1.1.03.03 (Inventario de
 *     productos en proceso) para los movimientos de producción, para que sus
 *     asientos salgan cuadrados solos:
 *       - se prepara:  Dr 1.1.03.03 (entrada_produccion) / Cr materia prima
 *       - se consume:  Cr 1.1.03.03 (salida_produccion)   / Dr producto terminado
 *
 *  2. Alinea los ítems espejo de semielaborados que en la Capa 1 habían nacido
 *     como `materia_prima` (la norma: un semielaborado no es materia prima comprada).
 */
return new class extends Migration
{
    public function up(): void
    {
        $cuenta = AccountPlan::whereNull('empresa_id')->where('code', '1.1.03.03')->first();

        if ($cuenta) {
            foreach (['entrada_produccion', 'salida_produccion', 'consumo_produccion'] as $mov) {
                AccountingMap::updateOrCreate(
                    ['empresa_id' => null, 'tipo_item' => 'materia_prima_procesada', 'tipo_movimiento' => $mov],
                    ['account_plan_id' => $cuenta->id],
                );
            }
        }

        // Semielaborados que nacieron como materia_prima en la Capa 1 → su tipo correcto.
        DB::statement("
            UPDATE inventory_items ii
            SET type = 'materia_prima_procesada', updated_at = NOW()
            FROM product_presentations pp
            JOIN product_designs pd ON pd.id = pp.product_design_id
            WHERE ii.product_presentation_id = pp.id
              AND pd.es_semielaborado = TRUE
              AND ii.type = 'materia_prima'
        ");
    }

    public function down(): void
    {
        AccountingMap::whereNull('empresa_id')
            ->where('tipo_item', 'materia_prima_procesada')
            ->delete();
    }
};
