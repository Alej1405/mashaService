<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El costo por unidad de consumo puede ser muy pequeño (glucosa a 0,002259 el gramo):
 * con 4 decimales quedaba en 0,0023 y el kardex valoraba 4.170 g en 9,59 en vez de 9,42.
 * Los costos unitarios pasan a 10 decimales; los valores siguen con 4.
 */
return new class extends Migration
{
    private const COLUMNAS = [
        ['inventory_items', 'costo_promedio'],
        ['inventory_movements', 'costo_promedio'],
        ['inventory_movements', 'unit_price'],
        ['production_materials', 'costo_unitario'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as [$tabla, $columna]) {
            DB::statement("alter table {$tabla} alter column {$columna} type numeric(24,10)");
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNAS as [$tabla, $columna]) {
            DB::statement("alter table {$tabla} alter column {$columna} type numeric(15,4)");
        }
    }
};
