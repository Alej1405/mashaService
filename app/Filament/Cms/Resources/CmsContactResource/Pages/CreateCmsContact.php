<?php

namespace App\Filament\Cms\Resources\CmsContactResource\Pages;

use App\Filament\Cms\Resources\CmsContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsContact extends CreateRecord
{
    protected static string $resource = CmsContactResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
