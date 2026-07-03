<?php

namespace App\Filament\Cms\Resources\CmsContactResource\Pages;

use App\Filament\Cms\Resources\CmsContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsContact extends EditRecord
{
    protected static string $resource = CmsContactResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
