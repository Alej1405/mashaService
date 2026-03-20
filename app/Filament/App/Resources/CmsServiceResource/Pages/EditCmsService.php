<?php
namespace App\Filament\App\Resources\CmsServiceResource\Pages;
use App\Filament\App\Resources\CmsServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCmsService extends EditRecord {
    protected static string $resource = CmsServiceResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
