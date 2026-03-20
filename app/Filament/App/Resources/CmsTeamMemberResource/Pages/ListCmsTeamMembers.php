<?php
namespace App\Filament\App\Resources\CmsTeamMemberResource\Pages;
use App\Filament\App\Resources\CmsTeamMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCmsTeamMembers extends ListRecords {
    protected static string $resource = CmsTeamMemberResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Agregar integrante')]; }
}
