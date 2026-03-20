<?php
namespace App\Filament\App\Resources\CmsTestimonialResource\Pages;
use App\Filament\App\Resources\CmsTestimonialResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCmsTestimonial extends CreateRecord {
    protected static string $resource = CmsTestimonialResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
