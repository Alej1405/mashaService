<?php

namespace App\Filament\App\Resources\InventoryItemResource\Pages;

use App\Filament\App\Pages\ImportarInventarioPage;
use App\Filament\App\Resources\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryItems extends ListRecords
{
    protected static string $resource = InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importar')
                ->label('Importar desde archivo')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(ImportarInventarioPage::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
