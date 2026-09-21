<?php

namespace App\Filament\Admin\Resources\SitioFaqResource\Pages;

use App\Filament\Admin\Resources\SitioFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioFaq extends EditRecord
{
    protected static string $resource = SitioFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
