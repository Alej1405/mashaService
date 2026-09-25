<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Todo lo del producto pasa al panel de Operaciones.
 *
 * El catálogo, la receta, el costo y la producción son la misma conversación y
 * estaban repartidos entre tres paneles. Operaciones ya tenía el inventario,
 * así que recibe también «tienda» y «produccion»: sin esos módulos, sus
 * pantallas responden 403 aunque el recurso esté en la carpeta.
 */
return new class extends Migration
{
    private const MODULOS = ['tienda', 'produccion'];

    public function up(): void
    {
        $panel = DB::table('panels')->where('key', 'operaciones')->first();

        if (! $panel) {
            return;
        }

        foreach (self::MODULOS as $modulo) {
            $existe = DB::table('panel_modules')
                ->where('panel_id', $panel->id)->where('module_key', $modulo)->exists();

            if (! $existe) {
                DB::table('panel_modules')->insert([
                    'panel_id'   => $panel->id,
                    'module_key' => $modulo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $panel = DB::table('panels')->where('key', 'operaciones')->first();

        if ($panel) {
            DB::table('panel_modules')->where('panel_id', $panel->id)
                ->whereIn('module_key', self::MODULOS)->delete();
        }
    }
};
