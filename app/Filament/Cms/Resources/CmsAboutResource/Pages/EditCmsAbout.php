<?php

namespace App\Filament\Cms\Resources\CmsAboutResource\Pages;

use App\Filament\Cms\Resources\CmsAboutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsAbout extends EditRecord
{
    protected static string $resource = CmsAboutResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
