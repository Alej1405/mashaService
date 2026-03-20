<?php
namespace App\Filament\App\Resources\CmsServiceResource\Pages;
use App\Filament\App\Resources\CmsServiceResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCmsService extends CreateRecord {
    protected static string $resource = CmsServiceResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
