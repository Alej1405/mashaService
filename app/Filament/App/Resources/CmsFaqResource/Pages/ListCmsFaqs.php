<?php
namespace App\Filament\App\Resources\CmsFaqResource\Pages;
use App\Filament\App\Resources\CmsFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCmsFaqs extends ListRecords {
    protected static string $resource = CmsFaqResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nueva pregunta')]; }
}
