<?php

namespace App\Filament\Cms\Resources\CmsClientLogoResource\Pages;

use App\Filament\Cms\Resources\CmsClientLogoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsClientLogo extends EditRecord
{
    protected static string $resource = CmsClientLogoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
