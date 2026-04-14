<?php

namespace App\Filament\App\Resources\ServiceDesignResource\Pages;

use App\Filament\App\Resources\ServiceDesignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceDesigns extends ListRecords
{
    protected static string $resource = ServiceDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
