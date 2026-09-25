<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Formulación: unidades sin ambigüedad, recetas con merma y materia prima
 * procesada.
 *
 * Un macerado no es materia prima comprada ni producto terminado: se produce
 * consumiendo otros insumos y después se consume en otra receta. Sin un tipo
 * propio acaba contabilizado como producto terminado y el balance miente.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Litros y Libras compartían la abreviatura «l»: en una receta eso es
        // la diferencia entre 700 mililitros y 317 kilos.
        DB::table('measurement_units')->where('nombre', 'like', 'Libra%')
            ->where('abreviatura', 'l')->update(['abreviatura' => 'lb']);

        // «Litro» y «Litros», «Gramo» y «Gramos»: la misma unidad dos veces.
        // Se conserva la más antigua y las demás se repuntan hacia ella.
        $referencias = [
            'inventory_items'        => ['measurement_unit_id', 'purchase_unit_id'],
            'product_formula_lines'  => ['measurement_unit_id'],
            'product_presentations'  => ['measurement_unit_id'],
            'item_presentations'     => ['measurement_unit_id'],
        ];

        $grupos = DB::table('measurement_units')
            ->selectRaw('empresa_id, tipo, factor, min(id) as se_queda')
            ->groupBy('empresa_id', 'tipo', 'factor')
            ->havingRaw('count(*) > 1')->get();

        foreach ($grupos as $g) {
            $sobran = DB::table('measurement_units')
                ->where('empresa_id', $g->empresa_id)->where('tipo', $g->tipo)
                ->where('factor', $g->factor)->where('id', '!=', $g->se_queda)
                ->pluck('id');

            foreach ($referencias as $tabla => $columnas) {
                if (! Schema::hasTable($tabla)) {
                    continue;
                }

                foreach ($columnas as $columna) {
                    if (Schema::hasColumn($tabla, $columna)) {
                        DB::table($tabla)->whereIn($columna, $sobran)->update([$columna => $g->se_queda]);
                    }
                }
            }

            DB::table('measurement_units')->whereIn('id', $sobran)->delete();
        }

        Schema::table('measurement_units', function (Blueprint $t) {
            $t->unique(['empresa_id', 'abreviatura'], 'unidades_abreviatura_unica');
        });

        Schema::table('product_formula_lines', function (Blueprint $t) {
            // Lo que se pierde en el proceso: evaporación, poso, recortes. Es
            // costo del producto mientras sea merma normal (NIC 2).
            $t->decimal('merma_porcentaje', 5, 2)->default(0)->after('cantidad');
        });

        // El costo que la fórmula dice que debería costar, para compararlo
        // contra el promedio real del kardex después de producir.
        Schema::table('product_presentations', function (Blueprint $t) {
            $t->decimal('costo_estandar', 14, 4)->nullable()->after('pvp_estimado');
            $t->timestamp('costo_calculado_en')->nullable()->after('costo_estandar');
        });

        // Un ítem que se produce necesita saber con qué receta.
        Schema::table('inventory_items', function (Blueprint $t) {
            $t->foreignId('product_presentation_id')->nullable()->after('presentation_id')
                ->constrained('product_presentations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('product_presentation_id'));
        Schema::table('product_presentations', fn (Blueprint $t) => $t->dropColumn(['costo_estandar', 'costo_calculado_en']));
        Schema::table('product_formula_lines', fn (Blueprint $t) => $t->dropColumn('merma_porcentaje'));
        Schema::table('measurement_units', fn (Blueprint $t) => $t->dropUnique('unidades_abreviatura_unica'));
    }
};
