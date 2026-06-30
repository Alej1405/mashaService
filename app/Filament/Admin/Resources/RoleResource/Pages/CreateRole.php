<?php

namespace App\Filament\Admin\Resources\RoleResource\Pages;

use App\Filament\Admin\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use SyncsModuleKeys;

    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->captureModuleKeys($data);
    }

    protected function afterCreate(): void
    {
        $this->syncModuleKeys();
    }
}
