<?php

namespace App\Filament\App\Resources\CashRegisterResource\Pages;

use App\Filament\App\Resources\CashRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCashRegister extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = CashRegisterResource::class;
}
