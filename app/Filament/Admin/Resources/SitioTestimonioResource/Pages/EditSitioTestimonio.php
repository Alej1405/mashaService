<?php

namespace App\Filament\Admin\Resources\SitioTestimonioResource\Pages;

use App\Filament\Admin\Resources\SitioTestimonioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioTestimonio extends EditRecord
{
    protected static string $resource = SitioTestimonioResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
