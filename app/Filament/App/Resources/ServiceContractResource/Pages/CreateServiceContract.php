<?php

namespace App\Filament\App\Resources\ServiceContractResource\Pages;

use App\Filament\App\Resources\ServiceContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceContract extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = ServiceContractResource::class;
}
