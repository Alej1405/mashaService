<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleModule;
use Illuminate\Database\Seeder;

/**
 * Baseline de módulos visibles por rol. Idempotente.
 *
 * Deriva del mapeo conceptual que ya describía EmpresaUserResource::roleDescription().
 * El super_admin lo ajusta luego desde /admin (RoleResource). Es solo VISIBILIDAD:
 * nunca apaga lógica (Observers / AccountingService).
 */
class RoleModuleSeeder extends Seeder
{
    private const TODOS = [
        'finanzas', 'tesoreria', 'compras', 'inventario',
        'ventas', 'produccion', 'marketing', 'tienda', 'logistica', 'clientes',
    ];

    private const MAPEO = [
        'super_admin'       => self::TODOS,
        'admin_empresa'     => self::TODOS,
        'contador'          => ['finanzas', 'tesoreria'],
        'inventario'        => ['inventario'],
        'marketing'         => ['marketing'],
        'cms_editor'        => ['marketing'],
        'ecommerce_manager' => ['tienda', 'clientes'],
    ];

    public function run(): void
    {
        foreach (self::MAPEO as $roleName => $moduleKeys) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            // Limpia los que ya no aplican y agrega los del baseline (idempotente).
            $role->modules()->whereNotIn('module_key', $moduleKeys ?: ['__none__'])->delete();

            foreach ($moduleKeys as $key) {
                RoleModule::updateOrCreate([
                    'role_id'    => $role->id,
                    'module_key' => $key,
                ]);
            }
        }
    }
}
