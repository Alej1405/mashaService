<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La presentación "Lote de 5040 unidades" (Botella Kaleido) tenía
 * measurement_unit_id = 6 (Cajas 12) y factor_conversion = 12,
 * lo que implicaría 5040 × 12 = 60,480 unidades por lote — incorrecto.
 * El item ya usa Unidad (id=1) como unidad base y su stock está en unidades.
 * Corrección: measurement_unit_id = 1 (Unidad), factor_conversion = 1.
 * Así 1 lote = 5040 × 1 = 5040 unidades (correcto).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Identificar la presentación por nombre y unidad incorrecta
        DB::table('item_presentations')
            ->where('nombre', 'like', '%5040%')
            ->where('measurement_unit_id', 6) // Cajas 12
            ->update([
                'measurement_unit_id' => 1,  // Unidad
                'factor_conversion'   => 1.0,
            ]);
    }

    public function down(): void
    {
        DB::table('item_presentations')
            ->where('nombre', 'like', '%5040%')
            ->where('measurement_unit_id', 1)
            ->update([
                'measurement_unit_id' => 6,
                'factor_conversion'   => 12.0,
            ]);
    }
};
