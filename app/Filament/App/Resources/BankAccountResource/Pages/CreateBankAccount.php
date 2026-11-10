<?php

namespace App\Filament\App\Resources\BankAccountResource\Pages;

use App\Filament\App\Resources\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = BankAccountResource::class;
}
