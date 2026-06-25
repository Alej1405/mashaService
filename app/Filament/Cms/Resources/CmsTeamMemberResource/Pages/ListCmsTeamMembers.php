<?php

namespace App\Filament\Cms\Resources\CmsTeamMemberResource\Pages;

use App\Filament\Cms\Resources\CmsTeamMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsTeamMembers extends ListRecords
{
    protected static string $resource = CmsTeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
