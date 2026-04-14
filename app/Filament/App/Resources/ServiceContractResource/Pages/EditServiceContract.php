<?php

namespace App\Filament\App\Resources\ServiceContractResource\Pages;

use App\Filament\App\Resources\ServiceContractResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceContract extends EditRecord
{
    protected static string $resource = ServiceContractResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
