<?php

namespace App\Filament\Ecommerce\Resources\StoreProductResource\Pages;

use App\Filament\Ecommerce\Resources\StoreProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStoreProduct extends CreateRecord
{
    protected static string $resource = StoreProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
