<?php

namespace App\Filament\App\Resources\CreditCardResource\Pages;

use App\Filament\App\Resources\CreditCardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditCard extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = CreditCardResource::class;
}
