<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migra los inventory_items que definían su conversión vía item_presentations
 * (capacidad × factor_conversion) al modelo ÚNICO purchase_unit_id + conversion_factor.
 * Crea una unidad de compra con el nombre de la presentación (familia conteo) y
 * limpia presentation_id. item_presentations queda solo para Producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        $items = DB::table('inventory_items')
            ->whereNotNull('presentation_id')
            ->whereNull('purchase_unit_id')
            ->get();

        foreach ($items as $it) {
            $pres = DB::table('item_presentations')->where('id', $it->presentation_id)->first();
            if (! $pres) {
                continue;
            }

            $factor = (float) $pres->capacidad * max((float) ($pres->factor_conversion ?? 1), 1.0);
            if ($factor <= 0) {
                $factor = 1;
            }

            // Unidad de compra = nombre de la presentación (empaque, familia conteo).
            $unitId = DB::table('measurement_units')
                ->where('empresa_id', $it->empresa_id)
                ->whereRaw('lower(nombre) = ?', [mb_strtolower(trim($pres->nombre))])
                ->value('id');

            if (! $unitId) {
                $unitId = DB::table('measurement_units')->insertGetId([
                    'empresa_id'  => $it->empresa_id,
                    'nombre'      => $pres->nombre,
                    'abreviatura' => mb_substr($pres->nombre, 0, 10),
                    'tipo'        => 'conteo',
                    'factor'      => 1,
                    'activo'      => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            DB::table('inventory_items')->where('id', $it->id)->update([
                'purchase_unit_id'  => $unitId,
                'conversion_factor' => $factor,
                'presentation_id'   => null,
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible a nivel de datos (no se reconstruye presentation_id). No-op.
    }
};
