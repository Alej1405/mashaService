<?php

namespace App\Filament\App\Resources\CostoFijoResource\Pages;

use App\Filament\App\Resources\CostoFijoResource;
use App\Models\CostoFijo;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;

class ListCostosFijos extends ListRecords
{
    protected static string $resource = CostoFijoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        $empresa = Filament::getTenant();
        if (!$empresa) return null;

        $total = CostoFijo::where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->get()
            ->sum('monto_mensual');

        return 'Total mensual estimado: $' . number_format($total, 2);
    }
}
