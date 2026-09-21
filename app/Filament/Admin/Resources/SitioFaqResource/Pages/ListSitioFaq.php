<?php

namespace App\Filament\Admin\Resources\SitioFaqResource\Pages;

use App\Filament\Admin\Resources\SitioFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioFaq extends ListRecords
{
    protected static string $resource = SitioFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
