<?php

namespace App\Filament\Admin\Resources\SitioServicioResource\Pages;

use App\Filament\Admin\Resources\SitioServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioServicio extends EditRecord
{
    protected static string $resource = SitioServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
