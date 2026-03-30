<?php

namespace App\Filament\App\Resources\ItemPresentationResource\Pages;

use App\Filament\App\Resources\ItemPresentationResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateItemPresentation extends CreateRecord
{
    protected static string $resource = ItemPresentationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = Filament::getTenant()->id;
        return $data;
    }
}
