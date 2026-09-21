<?php

namespace App\Filament\Admin\Resources\SitioTestimonioResource\Pages;

use App\Filament\Admin\Resources\SitioTestimonioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioTestimonio extends ListRecords
{
    protected static string $resource = SitioTestimonioResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
