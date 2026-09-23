<?php

namespace App\Filament\Contabilidad\Resources\RetencionResource\Pages;

use App\Filament\Contabilidad\Resources\RetencionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRetenciones extends ListRecords
{
    protected static string $resource = RetencionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nueva retención')];
    }

    public function getSubheading(): ?string
    {
        return 'Una sociedad bajo control de la SCVS es agente de retención: en cada compra retiene IVA y renta.';
    }
}
