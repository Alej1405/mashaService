<?php

namespace App\Filament\Admin\Resources\SitioInvestigacionResource\Pages;

use App\Filament\Admin\Resources\SitioInvestigacionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioInvestigacion extends EditRecord
{
    protected static string $resource = SitioInvestigacionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
