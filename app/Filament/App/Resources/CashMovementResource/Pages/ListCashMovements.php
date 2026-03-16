<?php

namespace App\Filament\App\Resources\CashMovementResource\Pages;

use App\Filament\App\Resources\CashMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashMovements extends ListRecords
{
    protected static string $resource = CashMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
