<?php

namespace App\Filament\Cms\Resources\CmsPostResource\Pages;

use App\Filament\Cms\Resources\CmsPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsPost extends CreateRecord
{
    protected static string $resource = CmsPostResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
