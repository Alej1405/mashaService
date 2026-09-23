<?php

namespace App\Filament\Contabilidad\Resources\AsientoResource\Pages;

use App\Filament\Contabilidad\Resources\AsientoResource;
use App\Models\JournalEntry;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListAsientos extends ListRecords
{
    protected static string $resource = AsientoResource::class;

    public function getSubheading(): ?string
    {
        $empresa = Filament::getTenant();
        $descuadrados = JournalEntry::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)->where('esta_cuadrado', false)->count();

        return $descuadrados
            ? "Atención: {$descuadrados} asiento(s) descuadrado(s). El ejercicio no se puede cerrar así."
            : 'Todos los asientos cuadran.';
    }
}
