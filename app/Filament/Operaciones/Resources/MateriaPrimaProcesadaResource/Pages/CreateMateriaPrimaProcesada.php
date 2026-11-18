<?php

namespace App\Filament\Operaciones\Resources\MateriaPrimaProcesadaResource\Pages;

use App\Filament\Operaciones\Resources\MateriaPrimaProcesadaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMateriaPrimaProcesada extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = MateriaPrimaProcesadaResource::class;

    /** La bandera no se deja al humano: aquí todo es semielaborado. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['es_semielaborado'] = true;

        return $data;
    }

    /**
     * Un semielaborado necesita una presentación para colgarle la receta. Se crea
     * una sola, interna, con el nombre y la unidad del semielaborado. El observer
     * de la presentación le genera su ítem de inventario (materia prima).
     */
    protected function afterCreate(): void
    {
        $design = $this->record;

        if (! $design->presentations()->exists()) {
            $design->presentations()->create([
                'nombre'                     => $design->nombre,
                'measurement_unit_id'        => $design->unidad_base_id,
                'cantidad_minima_produccion' => $design->cantidad_base ?: 1,
                'activa'                     => true,
            ]);
        }
    }
}
