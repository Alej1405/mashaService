<?php

namespace App\Filament\Ecommerce\Resources\StoreCategoryResource\Pages;

use App\Filament\Ecommerce\Resources\StoreCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreCategory extends EditRecord
{
    protected static string $resource = StoreCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
