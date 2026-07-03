<?php

namespace App\Filament\Cms\Resources\CmsTerminosResource\Pages;

use App\Filament\Cms\Resources\CmsTerminosResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsTerminos extends CreateRecord
{
    protected static string $resource = CmsTerminosResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
