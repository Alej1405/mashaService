<?php
namespace App\Filament\App\Resources\CmsServiceResource\Pages;
use App\Filament\App\Resources\CmsServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCmsServices extends ListRecords {
    protected static string $resource = CmsServiceResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nuevo servicio')]; }
}
