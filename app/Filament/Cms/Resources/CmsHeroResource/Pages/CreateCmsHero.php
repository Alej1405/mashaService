<?php

namespace App\Filament\Cms\Resources\CmsHeroResource\Pages;

use App\Filament\Cms\Resources\CmsHeroResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsHero extends CreateRecord
{
    protected static string $resource = CmsHeroResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
