<?php

namespace App\Filament\Admin\Resources\PanelResource\Pages;

use App\Filament\Admin\Resources\PanelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPanels extends ListRecords
{
    protected static string $resource = PanelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuevo panel'),
        ];
    }
}
