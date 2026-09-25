<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El tipo `materia_prima_procesada` para el inventario.
 *
 * Un macerado se produce consumiendo otros insumos y después se consume en
 * otra receta. No es materia prima comprada —no tiene factura— ni producto
 * terminado —no se vende—, y contabilizarlo como cualquiera de los dos deja el
 * balance diciendo algo que no es.
 */
return new class extends Migration
{
    private const TIPOS = [
        'insumo', 'materia_prima', 'materia_prima_procesada',
        'producto_terminado', 'activo_fijo', 'servicio',
    ];

    public function up(): void
    {
        $this->reescribirCheck(self::TIPOS);
    }

    public function down(): void
    {
        DB::table('inventory_items')->where('type', 'materia_prima_procesada')
            ->update(['type' => 'materia_prima']);

        $this->reescribirCheck(array_values(array_diff(self::TIPOS, ['materia_prima_procesada'])));
    }

    /** El check vive con nombre fijo desde la migración original. */
    private function reescribirCheck(array $tipos): void
    {
        $existe = DB::selectOne("select 1 as hay from pg_constraint where conname = 'inventory_items_type_check'");

        if ($existe) {
            DB::statement('ALTER TABLE inventory_items DROP CONSTRAINT inventory_items_type_check');
        }

        $lista = implode(', ', array_map(fn ($t) => "'{$t}'", $tipos));

        DB::statement("ALTER TABLE inventory_items ADD CONSTRAINT inventory_items_type_check CHECK (type IN ({$lista}))");
    }
};
