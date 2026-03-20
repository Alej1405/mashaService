<?php
namespace App\Filament\App\Resources\CmsTestimonialResource\Pages;
use App\Filament\App\Resources\CmsTestimonialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCmsTestimonials extends ListRecords {
    protected static string $resource = CmsTestimonialResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nuevo testimonio')]; }
}
