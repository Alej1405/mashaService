<?php

namespace App\Filament\Admin\Resources\SystemEventResource\Pages;

use App\Filament\Admin\Resources\SystemEventResource;
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
