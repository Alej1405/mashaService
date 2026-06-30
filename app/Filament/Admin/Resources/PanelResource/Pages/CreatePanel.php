<?php

namespace App\Filament\Admin\Resources\PanelResource\Pages;

use App\Filament\Admin\Resources\PanelResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePanel extends CreateRecord
{
    use SyncsModuleKeys;

    protected static string $resource = PanelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->captureModuleKeys($data);
    }

    protected function afterCreate(): void
    {
        $this->syncModuleKeys();
    }
}
