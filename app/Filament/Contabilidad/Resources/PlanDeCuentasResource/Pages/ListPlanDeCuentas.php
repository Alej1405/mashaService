<?php

namespace App\Filament\Contabilidad\Resources\PlanDeCuentasResource\Pages;

use App\Filament\Contabilidad\Resources\PlanDeCuentasResource;
use App\Services\ContabilidadService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListPlanDeCuentas extends ListRecords
{
    protected static string $resource = PlanDeCuentasResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nueva cuenta')];
    }

    public function getSubheading(): ?string
    {
        $empresa = Filament::getTenant();
        $sinMapear = app(\App\Services\MapeoSuperciasService::class)->porRevisar($empresa->id);
        $sinCodigo = app(\App\Services\SuperciasService::class)->cuentasSinCodigo($empresa->id);
        $total = \App\Models\AccountPlan::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count();

        return $sinMapear > 0
            ? "{$total} cuentas · {$sinMapear} con la línea del estado sin confirmar"
            : "{$total} cuentas · todas con su línea del estado confirmada";
    }
}
