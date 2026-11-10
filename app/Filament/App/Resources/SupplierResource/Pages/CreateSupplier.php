<?php

namespace App\Filament\App\Resources\SupplierResource\Pages;

use App\Filament\App\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = SupplierResource::class;
}
