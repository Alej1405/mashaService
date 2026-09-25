<?php

namespace App\Filament\Operaciones\Resources\StoreProductResource\Pages;

use App\Filament\Operaciones\Resources\StoreProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStoreProduct extends CreateRecord
{
    protected static string $resource = StoreProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
