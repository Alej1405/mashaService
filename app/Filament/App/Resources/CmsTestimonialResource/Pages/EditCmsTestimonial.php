<?php
namespace App\Filament\App\Resources\CmsTestimonialResource\Pages;
use App\Filament\App\Resources\CmsTestimonialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCmsTestimonial extends EditRecord {
    protected static string $resource = CmsTestimonialResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
