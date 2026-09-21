<?php

namespace App\Filament\Admin\Resources\SitioCasoResource\Pages;

use App\Filament\Admin\Resources\SitioCasoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioCaso extends EditRecord
{
    protected static string $resource = SitioCasoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
