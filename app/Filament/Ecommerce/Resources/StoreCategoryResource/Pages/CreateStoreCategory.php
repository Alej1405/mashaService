<?php

namespace App\Filament\Ecommerce\Resources\StoreCategoryResource\Pages;

use App\Filament\Ecommerce\Resources\StoreCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStoreCategory extends CreateRecord
{
    protected static string $resource = StoreCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
