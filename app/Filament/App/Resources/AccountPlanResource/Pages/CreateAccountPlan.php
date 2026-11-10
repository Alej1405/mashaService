<?php

namespace App\Filament\App\Resources\AccountPlanResource\Pages;

use App\Filament\App\Resources\AccountPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountPlan extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = AccountPlanResource::class;
}
