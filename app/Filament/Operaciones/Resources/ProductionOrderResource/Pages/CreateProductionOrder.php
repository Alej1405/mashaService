<?php

namespace App\Filament\Operaciones\Resources\ProductionOrderResource\Pages;

use App\Filament\Operaciones\Resources\ProductionOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionOrder extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = ProductionOrderResource::class;
}
