<?php

namespace App\Filament\Admin\Resources\PanelResource\Pages;

use App\Filament\Admin\Resources\PanelResource;
use Filament\Resources\Pages\EditRecord;

class EditPanel extends EditRecord
{
    use SyncsModuleKeys;

    protected static string $resource = PanelResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->captureModuleKeys($data);
    }

    protected function afterSave(): void
    {
        $this->syncModuleKeys();
    }
}
