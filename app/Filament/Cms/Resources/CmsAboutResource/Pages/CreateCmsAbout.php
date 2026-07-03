<?php

namespace App\Filament\Cms\Resources\CmsAboutResource\Pages;

use App\Filament\Cms\Resources\CmsAboutResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsAbout extends CreateRecord
{
    protected static string $resource = CmsAboutResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
