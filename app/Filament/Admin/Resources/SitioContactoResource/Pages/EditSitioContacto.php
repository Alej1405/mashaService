<?php

namespace App\Filament\Admin\Resources\SitioContactoResource\Pages;

use App\Filament\Admin\Resources\SitioContactoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioContacto extends EditRecord
{
    protected static string $resource = SitioContactoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
