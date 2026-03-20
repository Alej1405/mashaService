<?php
namespace App\Filament\App\Resources\CmsPostResource\Pages;
use App\Filament\App\Resources\CmsPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCmsPost extends EditRecord {
    protected static string $resource = CmsPostResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
