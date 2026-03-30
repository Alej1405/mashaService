<?php

namespace App\Filament\App\Resources\ItemPresentationResource\Pages;

use App\Filament\App\Resources\ItemPresentationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItemPresentation extends EditRecord
{
    protected static string $resource = ItemPresentationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
