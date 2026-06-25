<?php

namespace App\Filament\Cms\Resources\CmsHeroResource\Pages;

use App\Filament\Cms\Resources\CmsHeroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsHero extends EditRecord
{
    protected static string $resource = CmsHeroResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
