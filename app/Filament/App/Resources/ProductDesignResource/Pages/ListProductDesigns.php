<?php

namespace App\Filament\App\Resources\ProductDesignResource\Pages;

use App\Filament\App\Resources\ProductDesignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductDesigns extends ListRecords
{
    protected static string $resource = ProductDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
