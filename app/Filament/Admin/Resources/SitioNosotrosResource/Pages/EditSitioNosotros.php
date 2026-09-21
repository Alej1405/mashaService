<?php

namespace App\Filament\Admin\Resources\SitioNosotrosResource\Pages;

use App\Filament\Admin\Resources\SitioNosotrosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioNosotros extends EditRecord
{
    protected static string $resource = SitioNosotrosResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
