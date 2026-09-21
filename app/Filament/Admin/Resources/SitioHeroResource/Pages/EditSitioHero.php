<?php

namespace App\Filament\Admin\Resources\SitioHeroResource\Pages;

use App\Filament\Admin\Resources\SitioHeroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioHero extends EditRecord
{
    protected static string $resource = SitioHeroResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
