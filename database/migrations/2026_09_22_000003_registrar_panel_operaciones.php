<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El panel de Operaciones, en la base.
 *
 * El panel existe como código desde que se creó `OperacionesPanelProvider`,
 * pero `PlanHelper::hasModule()` lo busca en la tabla `panels`: sin esa fila,
 * `canAccess()` devuelve false y cada recurso del panel responde 403. El
 * dashboard abre igual porque no define `canAccess`, y de ahí la sensación de
 * que "solo funciona el inicio".
 *
 * La fila estaba puesta a mano en desarrollo y nunca viajó al servidor. Esto
 * la deja en la migración, que es lo que sí se despliega.
 */
return new class extends Migration
{
    public function up(): void
    {
        $panelId = DB::table('panels')->where('key', 'operaciones')->value('id');

        if (! $panelId) {
            $panelId = DB::table('panels')->insertGetId([
                'key'        => 'operaciones',
                'name'       => 'Operaciones',
                'path'       => 'operaciones',
                'color'      => 'indigo',
                'icon'       => 'heroicon-o-cube',
                'activo'     => true,
                'sort'       => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // El módulo que el panel muestra. Sin esto, hasModule() sigue en false.
        $tieneModulo = DB::table('panel_modules')
            ->where('panel_id', $panelId)
            ->where('module_key', 'inventario')
            ->exists();

        if (! $tieneModulo) {
            DB::table('panel_modules')->insert([
                'panel_id'   => $panelId,
                'module_key' => 'inventario',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Qué planes lo abren. Pro y Enterprise incluyen inventario; sin al
        // menos uno, canAccessTenant falla y la empresa ni siquiera resuelve.
        // Esto es el punto de partida: se ajusta después desde /admin.
        $planes = DB::table('service_plans')->whereIn('key', ['pro', 'enterprise'])->pluck('id');

        foreach ($planes as $planId) {
            $yaEsta = DB::table('plan_panel')
                ->where('panel_id', $panelId)
                ->where('service_plan_id', $planId)
                ->exists();

            if (! $yaEsta) {
                DB::table('plan_panel')->insert([
                    'service_plan_id' => $planId,
                    'panel_id'        => $panelId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $panelId = DB::table('panels')->where('key', 'operaciones')->value('id');

        if (! $panelId) {
            return;
        }

        DB::table('plan_panel')->where('panel_id', $panelId)->delete();
        DB::table('panel_modules')->where('panel_id', $panelId)->delete();
        DB::table('panels')->where('id', $panelId)->delete();
    }
};
