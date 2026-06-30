<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    use SyncsModuleKeys;

    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->captureModuleKeys($data);
    }

    protected function afterSave(): void
    {
        $this->syncModuleKeys();
    }
}
