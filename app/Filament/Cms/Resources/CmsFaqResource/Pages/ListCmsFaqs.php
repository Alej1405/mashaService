<?php

namespace App\Filament\Cms\Resources\CmsFaqResource\Pages;

use App\Filament\Cms\Resources\CmsFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsFaqs extends ListRecords
{
    protected static string $resource = CmsFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
