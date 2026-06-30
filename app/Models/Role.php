<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Rol de usuario (extiende el de Spatie) con la capa de módulos visibles.
 *
 * El super_admin crea los roles y define qué módulos ve cada uno (role_module).
 * La empresa solo asigna el rol a sus usuarios (empresa_user_access.rol).
 *
 * La visibilidad de un módulo para un usuario = módulos del panel actual
 * ∩ módulos de su rol. Los módulos siempre funcionan en segundo plano.
 */
class Role extends SpatieRole
{
    /** Módulos asociados a este rol. */
    public function modules(): HasMany
    {
        return $this->hasMany(RoleModule::class);
    }

    /** Claves de los módulos visibles para este rol. */
    public function moduleKeys(): array
    {
        return $this->modules()->pluck('module_key')->all();
    }

    /** ¿Este rol puede ver el módulo dado? */
    public function showsModule(string $moduleKey): bool
    {
        return $this->modules()->where('module_key', $moduleKey)->exists();
    }
}
