<?php

namespace App\Filament\App\Resources\ProductionOrderResource\Pages;

use App\Filament\App\Resources\ProductionOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionOrders extends ListRecords
{
    protected static string $resource = ProductionOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
