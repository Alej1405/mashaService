<?php

namespace App\Filament\Cms\Resources\CmsTestimonialResource\Pages;

use App\Filament\Cms\Resources\CmsTestimonialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsTestimonial extends EditRecord
{
    protected static string $resource = CmsTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
