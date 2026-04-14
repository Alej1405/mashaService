<?php

namespace App\Filament\App\Resources\CmsProductResource\Pages;

use App\Filament\App\Resources\CmsProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsProduct extends EditRecord
{
    protected static string $resource = CmsProductResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
