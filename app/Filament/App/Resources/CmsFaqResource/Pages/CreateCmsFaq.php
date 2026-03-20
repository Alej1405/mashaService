<?php
namespace App\Filament\App\Resources\CmsFaqResource\Pages;
use App\Filament\App\Resources\CmsFaqResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCmsFaq extends CreateRecord {
    protected static string $resource = CmsFaqResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
