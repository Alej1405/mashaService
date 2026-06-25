<?php

namespace App\Filament\Cms\Resources\CmsTeamMemberResource\Pages;

use App\Filament\Cms\Resources\CmsTeamMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsTeamMember extends EditRecord
{
    protected static string $resource = CmsTeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
