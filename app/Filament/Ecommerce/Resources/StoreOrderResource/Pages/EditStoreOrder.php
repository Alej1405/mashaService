<?php

namespace App\Filament\Ecommerce\Resources\StoreOrderResource\Pages;

use App\Filament\Ecommerce\Resources\StoreOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreOrder extends EditRecord
{
    protected static string $resource = StoreOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
