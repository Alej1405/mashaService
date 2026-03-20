<?php
namespace App\Filament\App\Resources\CmsClientLogoResource\Pages;
use App\Filament\App\Resources\CmsClientLogoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCmsClientLogos extends ListRecords {
    protected static string $resource = CmsClientLogoResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Agregar cliente')]; }
}
