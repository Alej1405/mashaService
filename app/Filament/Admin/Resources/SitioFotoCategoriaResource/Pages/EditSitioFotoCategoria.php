<?php

namespace App\Filament\Admin\Resources\SitioFotoCategoriaResource\Pages;

use App\Filament\Admin\Resources\SitioFotoCategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioFotoCategoria extends EditRecord
{
    protected static string $resource = SitioFotoCategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
