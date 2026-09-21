<?php

namespace App\Filament\Admin\Resources\SitioPaginaResource\Pages;

use App\Filament\Admin\Resources\SitioPaginaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioPagina extends EditRecord
{
    protected static string $resource = SitioPaginaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
