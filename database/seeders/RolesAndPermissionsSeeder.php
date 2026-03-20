<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create core roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin      = Role::firstOrCreate(['name' => 'admin_empresa']);
        $contador   = Role::firstOrCreate(['name' => 'contador']);
        $inventario = Role::firstOrCreate(['name' => 'inventario']);
        $marketing  = Role::firstOrCreate(['name' => 'marketing']);

        // Define permissions
        $permissions = [
            'reportes.ver',
            'reportes.exportar',
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'proveedores.eliminar',
            'inventario.ver',
            'inventario.crear',
            'inventario.editar',
            'inventario.eliminar',
            // Mailing
            'mailing.ver',
            'mailing.contactos.gestionar',
            'mailing.plantillas.gestionar',
            'mailing.campanas.gestionar',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $admin->syncPermissions($permissions);

        $contador->syncPermissions([
            'reportes.ver',
            'reportes.exportar',
            'proveedores.ver',
            'inventario.ver',
        ]);

        $inventario->syncPermissions([
            'proveedores.ver',
            'inventario.ver',
            'inventario.crear',
            'inventario.editar',
        ]);

        // Marketing: solo acceso al módulo mailing (panel básico)
        $marketing->syncPermissions([
            'mailing.ver',
            'mailing.contactos.gestionar',
            'mailing.plantillas.gestionar',
            'mailing.campanas.gestionar',
        ]);

        // Assign super_admin role to the initial admin user
        $adminUser = User::where('email', 'admin@mashaec.net')->first();
        if ($adminUser) {
            $adminUser->assignRole('super_admin');
        }
    }
}
