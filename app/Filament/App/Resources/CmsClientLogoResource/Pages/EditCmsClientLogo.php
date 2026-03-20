<?php
namespace App\Filament\App\Resources\CmsClientLogoResource\Pages;
use App\Filament\App\Resources\CmsClientLogoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCmsClientLogo extends EditRecord {
    protected static string $resource = CmsClientLogoResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
