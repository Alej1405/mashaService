<?php

namespace Database\Seeders;

use App\Models\Panel;
use App\Models\PanelModule;
use App\Models\ServicePlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fase 3 — Estandariza los 6 paneles operativos REALES (los Filament panels)
 * y siembra la relación N:M plan ↔ panel replicando el acceso actual.
 *
 * Separación de conceptos (antes mezclados 1:1 plan=panel):
 *   - PANEL   = contenedor Filament fijo, con su path. Aquí se estandarizan.
 *   - PLAN    = entidad creable/renombrable (service_plans).
 *   - plan_panel = qué paneles abre cada plan (administrable desde el admin).
 *   - panel_modules = qué módulos muestra cada panel.
 *
 * El seed de plan_panel reproduce EXACTAMENTE el baseline de acceso por
 * niveles (User::PLAN_LEVELS) para garantizar cero regresión:
 *   basic      → basic
 *   pro        → basic, pro
 *   enterprise → basic, pro, enterprise, logistics
 *   prueba     → basic
 * (cms y ecommerce siguen siendo role-based en User, NO van por plan_panel).
 *
 * Idempotente: updateOrCreate + sync. Seguro para re-ejecutar.
 */
class PanelSeeder extends Seeder
{
    /** Los 6 paneles operativos reales (key == id del Filament PanelProvider). */
    private const PANELS = [
        'basic'      => ['name' => 'Basic',      'path' => 'app',        'color' => 'slate',  'icon' => 'heroicon-o-squares-2x2',       'sort' => 1, 'modules' => ['marketing']],
        'pro'        => ['name' => 'ERP',        'path' => 'pro',        'color' => 'indigo', 'icon' => 'heroicon-o-building-office-2',  'sort' => 2, 'modules' => ['finanzas', 'tesoreria', 'compras', 'inventario', 'ventas', 'produccion', 'marketing', 'logistica']],
        'enterprise' => ['name' => 'Enterprise', 'path' => 'enterprise', 'color' => 'amber',  'icon' => 'heroicon-o-star',              'sort' => 3, 'modules' => ['finanzas', 'tesoreria', 'compras', 'inventario', 'ventas', 'produccion', 'marketing', 'tienda', 'logistica']],
        'logistics'  => ['name' => 'Logística',  'path' => 'logistics',  'color' => 'cyan',   'icon' => 'heroicon-o-truck',             'sort' => 4, 'modules' => ['logistica']],
        'cms'        => ['name' => 'CMS',        'path' => 'cms',        'color' => 'violet', 'icon' => 'heroicon-o-globe-alt',         'sort' => 5, 'modules' => ['marketing']],
        'ecommerce'  => ['name' => 'Tienda',     'path' => 'store',      'color' => 'cyan',   'icon' => 'heroicon-o-shopping-bag',      'sort' => 6, 'modules' => ['tienda']],
    ];

    /** plan(key) → paneles(key) que abre. Replica el baseline de acceso por niveles. */
    private const PLAN_PANELS = [
        'basic'      => ['basic'],
        'pro'        => ['basic', 'pro'],
        'enterprise' => ['basic', 'pro', 'enterprise', 'logistics'],
        'prueba'     => ['basic'],
    ];

    public function run(): void
    {
        $catalogo = array_keys(config('erp_features', []));

        // 1) Estandarizar los 6 paneles reales + sus módulos.
        foreach (self::PANELS as $key => $meta) {
            $panel = Panel::updateOrCreate(
                ['key' => $key],
                [
                    'name'   => $meta['name'],
                    'path'   => $meta['path'],
                    'color'  => $meta['color'],
                    'icon'   => $meta['icon'],
                    'activo' => true,
                    'sort'   => $meta['sort'],
                ]
            );

            // Solo módulos que existan en el catálogo real.
            $modulos = array_values(array_intersect($meta['modules'], $catalogo));
            $panel->modules()->whereNotIn('module_key', $modulos)->delete();
            foreach ($modulos as $moduleKey) {
                PanelModule::updateOrCreate(['panel_id' => $panel->id, 'module_key' => $moduleKey]);
            }
        }

        // 2) Paneles heredados que ya NO son operativos (ej. 'prueba' era un plan): desactivar.
        Panel::whereNotIn('key', array_keys(self::PANELS))->update(['activo' => false]);

        // 3) Sembrar plan_panel replicando el baseline de acceso.
        $panelIdByKey = Panel::pluck('id', 'key');
        foreach (ServicePlan::all() as $plan) {
            $panelKeys = self::PLAN_PANELS[$plan->key] ?? ['basic'];
            $panelIds  = collect($panelKeys)
                ->map(fn ($k) => $panelIdByKey[$k] ?? null)
                ->filter()
                ->values()
                ->all();

            $plan->panels()->sync($panelIds);
        }
    }
}
