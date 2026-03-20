<?php
namespace App\Filament\App\Resources\CmsFaqResource\Pages;
use App\Filament\App\Resources\CmsFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCmsFaq extends EditRecord {
    protected static string $resource = CmsFaqResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
