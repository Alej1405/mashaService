<?php

namespace App\Filament\Operaciones\Resources\StoreProductResource\Pages;

use App\Filament\Operaciones\Resources\StoreProductResource;
use Filament\Resources\Pages\ListRecords;

class ListStoreProducts extends ListRecords
{
    protected static string $resource = StoreProductResource::class;

    protected function getHeaderActions(): array
    {
        // Un producto nace con sus presentaciones, como en Diseño de producto.
        return [\App\Filament\Compartido\CrearProducto::accion()];
    }
}
