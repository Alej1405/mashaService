<?php

namespace App\Filament\App\Resources\AccountPlanResource\Pages;

use App\Filament\App\Resources\AccountPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountPlan extends EditRecord
{
    protected static string $resource = AccountPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
