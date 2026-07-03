<?php

namespace App\Filament\Cms\Resources\CmsClientLogoResource\Pages;

use App\Filament\Cms\Resources\CmsClientLogoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsClientLogo extends CreateRecord
{
    protected static string $resource = CmsClientLogoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
