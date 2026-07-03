<?php

namespace App\Filament\App\Resources\StoreProductResource\Pages;

use App\Filament\App\Resources\StoreProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreProduct extends EditRecord
{
    protected static string $resource = StoreProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
