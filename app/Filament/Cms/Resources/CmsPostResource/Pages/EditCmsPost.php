<?php

namespace App\Filament\Cms\Resources\CmsPostResource\Pages;

use App\Filament\Cms\Resources\CmsPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsPost extends EditRecord
{
    protected static string $resource = CmsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
