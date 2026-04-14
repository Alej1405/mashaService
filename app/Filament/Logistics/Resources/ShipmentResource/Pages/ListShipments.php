<?php

namespace App\Filament\Logistics\Resources\ShipmentResource\Pages;

use App\Filament\Logistics\Resources\ShipmentResource;
use App\Filament\Logistics\Pages\ShipmentKanban;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Ver tablero Kanban')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(ShipmentKanban::getUrl(tenant: Filament::getTenant())),
            CreateAction::make(),
        ];
    }
}
