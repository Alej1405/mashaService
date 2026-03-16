<?php

namespace App\Filament\App\Resources\PurchaseResource\Pages;

use App\Filament\App\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePurchases extends ManageRecords
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
