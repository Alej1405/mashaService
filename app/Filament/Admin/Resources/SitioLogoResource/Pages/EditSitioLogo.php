<?php

namespace App\Filament\Admin\Resources\SitioLogoResource\Pages;

use App\Filament\Admin\Resources\SitioLogoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioLogo extends EditRecord
{
    protected static string $resource = SitioLogoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
