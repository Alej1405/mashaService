<?php

namespace App\Filament\Ecommerce\Resources\StoreProductResource\Pages;

use App\Filament\Ecommerce\Resources\StoreProductResource;
use App\Modules\Ecommerce\Actions\AsegurarInventarioProducto;
use Filament\Resources\Pages\CreateRecord;

class CreateStoreProduct extends CreateRecord
{
    protected static string $resource = StoreProductResource::class;

    /** Existencias iniciales capturadas en el formulario (no es columna del producto). */
    protected float $existenciasIniciales = 0;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->existenciasIniciales = (float) ($data['existencias_iniciales'] ?? 0);
        unset($data['existencias_iniciales']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Todo producto creado desde la tienda obtiene automáticamente su item de
        // inventario (producto terminado) vinculado 1:1. Sin vínculo manual aquí.
        app(AsegurarInventarioProducto::class)->handle($this->record, $this->existenciasIniciales);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }
}
