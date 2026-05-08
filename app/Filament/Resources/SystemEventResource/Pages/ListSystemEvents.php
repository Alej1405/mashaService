<?php

namespace App\Filament\Resources\SystemEventResource\Pages;

use App\Filament\Resources\SystemEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemEvents extends ListRecords
{
    protected static string $resource = SystemEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Registrar evento manual'),
        ];
    }
}
