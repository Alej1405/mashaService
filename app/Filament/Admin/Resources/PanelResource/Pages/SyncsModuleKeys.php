<?php

namespace App\Filament\Admin\Resources\PanelResource\Pages;

use App\Models\PanelModule;

/**
 * Captura el CheckboxList `module_keys` (que no es columna de `panels`) y
 * sincroniza el pivote `panel_modules` tras guardar. Compartido por las
 * páginas Create y Edit de PanelResource.
 */
trait SyncsModuleKeys
{
    /** @var array<int,string> */
    protected array $moduleKeysToSync = [];

    /** Extrae module_keys del data del form para que no llegue al ->save() del modelo. */
    protected function captureModuleKeys(array $data): array
    {
        $this->moduleKeysToSync = $data['module_keys'] ?? [];
        unset($data['module_keys']);

        return $data;
    }

    /** Deja en panel_modules exactamente las claves seleccionadas. */
    protected function syncModuleKeys(): void
    {
        $panel = $this->record;
        $keys  = $this->moduleKeysToSync;

        $panel->modules()
            ->whereNotIn('module_key', $keys ?: ['__none__'])
            ->delete();

        foreach ($keys as $key) {
            PanelModule::updateOrCreate(['panel_id' => $panel->id, 'module_key' => $key]);
        }
    }
}
