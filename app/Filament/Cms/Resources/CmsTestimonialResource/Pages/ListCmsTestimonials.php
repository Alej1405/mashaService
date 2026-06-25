<?php

namespace App\Filament\Cms\Resources\CmsTestimonialResource\Pages;

use App\Filament\Cms\Resources\CmsTestimonialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsTestimonials extends ListRecords
{
    protected static string $resource = CmsTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
