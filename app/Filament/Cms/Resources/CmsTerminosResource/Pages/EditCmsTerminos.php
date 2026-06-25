<?php

namespace App\Filament\Cms\Resources\CmsTerminosResource\Pages;

use App\Filament\Cms\Resources\CmsTerminosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsTerminos extends EditRecord
{
    protected static string $resource = CmsTerminosResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
