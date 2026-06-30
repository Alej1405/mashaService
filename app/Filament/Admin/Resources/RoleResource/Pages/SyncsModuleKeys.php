<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Models\RoleModule;

/**
 * Sincroniza la selección de módulos del formulario con la tabla pivote role_module.
 * 'module_keys' no es una columna del rol: se captura antes de guardar y se aplica después.
 */
trait SyncsModuleKeys
{
    protected array $moduleKeysToSync = [];

    protected function captureModuleKeys(array $data): array
    {
        $this->moduleKeysToSync = $data['module_keys'] ?? [];
        unset($data['module_keys']);

        return $data;
    }

    protected function syncModuleKeys(): void
    {
        $role = $this->record;
        $keys = $this->moduleKeysToSync;

        $role->modules()->whereNotIn('module_key', $keys ?: ['__none__'])->delete();

        foreach ($keys as $key) {
            RoleModule::updateOrCreate([
                'role_id'    => $role->id,
                'module_key' => $key,
            ]);
        }
    }
}
