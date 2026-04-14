<?php

namespace App\Filament\Logistics\Resources\ConsignatarioResource\Pages;

use App\Filament\Logistics\Resources\ConsignatarioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConsignatario extends EditRecord
{
    protected static string $resource = ConsignatarioResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
