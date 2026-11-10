<?php

namespace App\Filament\App\Resources\SaleResource\Pages;

use App\Filament\App\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = SaleResource::class;
}
