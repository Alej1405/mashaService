<?php

namespace App\Filament\Admin\Resources\SitioArticuloResource\Pages;

use App\Filament\Admin\Resources\SitioArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioArticulo extends EditRecord
{
    protected static string $resource = SitioArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
