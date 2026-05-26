<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige la arquitectura de conversión de unidades entre presentaciones e ítems.
 *
 * Problema: item_presentations.factor_conversion estaba en 1 para todos los registros.
 * El stock_actual de ítems con presentaciones de volumen/masa fue ingresado como
 * "número de envases" en lugar de la unidad base (ml, g). Esta migración:
 *
 * 1. Calcula y guarda factor_conversion correcto en cada presentación.
 * 2. Recalcula stock_actual de ítems de tipo ml/g que tienen presentaciones y
 *    no poseen movimientos de inventario (fueron ingresados manualmente como envases).
 * 3. Ajusta purchase_unit_id y conversion_factor de esos ítems para coherencia futura.
 *
 * Ítems discretos (Unidad) NO se tocan: su stock ya está en la unidad base correcta.
 */
return new class extends Migration
{
    // Conversión: presentation_measurement_unit_id → item_measurement_unit_id → factor
    private array $unitFactors = [
        8 => [2 => 1000.0],   // Litros  → Mililitro : ×1000
        4 => [3 => 1000.0],   // Kilos   → Gramos    : ×1000
        6 => [1 => 12.0],     // Cajas12 → Unidad    : ×12
        7 => [1 => 24.0],     // Cajas24 → Unidad    : ×24
    ];

    // Unidades de ítems que representan magnitudes continuas (ml, g)
    // Solo estos se recalculan; Unidad queda intacta.
    private array $continuousUnits = [2, 3]; // Mililitro, Gramos

    public function up(): void
    {
        // ── Paso 0: ampliar precisión de stock_actual e inventory_movements ────
        // DECIMAL(10,4) tiene máximo ~999,999. Ítems en ml/g pueden superar eso.
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('stock_actual', 16, 4)->default(0)->change();
            $table->decimal('stock_minimo', 16, 4)->default(0)->change();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('quantity', 16, 4)->change();
        });

        // ── Paso 1: fijar factor_conversion en item_presentations ──────────────
        $presentations = DB::table('item_presentations')
            ->whereNotNull('measurement_unit_id')
            ->get(['id', 'measurement_unit_id', 'capacidad']);

        foreach ($presentations as $pres) {
            // Tomamos el primer ítem vinculado para saber la unidad base del ítem
            $item = DB::table('inventory_items')
                ->where('presentation_id', $pres->id)
                ->whereNotNull('measurement_unit_id')
                ->first(['measurement_unit_id']);

            if (! $item) {
                continue;
            }

            $presUnitId  = (int) $pres->measurement_unit_id;
            $itemUnitId  = (int) $item->measurement_unit_id;
            $factor      = $this->unitFactors[$presUnitId][$itemUnitId] ?? 1.0;

            DB::table('item_presentations')
                ->where('id', $pres->id)
                ->update(['factor_conversion' => $factor]);
        }

        // ── Paso 2: recalcular stock de ítems con presentaciones (ml y g) ──────
        //
        // Solo se tocan ítems que:
        //   a) tienen presentación con unidad de volumen/masa (Litros, Kilos)
        //   b) son ellos mismos de unidad continua (ml, g)
        //   c) NO tienen movimientos de inventario (su stock fue ingresado manual)
        //   d) tienen stock > 0
        //
        $rows = DB::table('inventory_items as ii')
            ->join('item_presentations as ip', 'ii.presentation_id', '=', 'ip.id')
            ->whereIn('ii.measurement_unit_id', $this->continuousUnits)
            ->where('ii.stock_actual', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('inventory_movements')
                    ->whereColumn('inventory_movements.inventory_item_id', 'ii.id');
            })
            ->select(
                'ii.id',
                'ii.stock_actual',
                'ii.nombre',
                'ip.capacidad',
                'ip.factor_conversion',
                'ip.measurement_unit_id as pres_unit_id'
            )
            ->get();

        foreach ($rows as $row) {
            $factor    = max((float) $row->factor_conversion, 0.000001);
            $capacidad = (float) $row->capacidad;
            $newStock  = round((float) $row->stock_actual * $capacidad * $factor, 4);

            DB::table('inventory_items')
                ->where('id', $row->id)
                ->update([
                    'stock_actual'     => $newStock,
                    // Alinear purchase_unit con la unidad de presentación
                    'purchase_unit_id' => (int) $row->pres_unit_id,
                    // factor = unidades_base por unidad_de_compra (igual a factor_conversion)
                    'conversion_factor' => $factor,
                ]);

            // Registrar en log para trazabilidad (sin exception si falla)
            try {
                \Illuminate\Support\Facades\Log::info('Migración stock corregido', [
                    'item_id'       => $row->id,
                    'nombre'        => $row->nombre,
                    'stock_antes'   => $row->stock_actual,
                    'stock_despues' => $newStock,
                    'capacidad'     => $capacidad,
                    'factor'        => $factor,
                ]);
            } catch (\Throwable) {
            }
        }
    }

    public function down(): void
    {
        // No se puede revertir de forma segura: respalda antes de migrar.
        // Para revertir manualmente: restaurar stock_actual, purchase_unit_id,
        // conversion_factor e item_presentations.factor_conversion desde backup.
    }
};
