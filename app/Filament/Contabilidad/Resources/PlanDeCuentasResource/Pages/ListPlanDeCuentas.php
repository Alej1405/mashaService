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
        $sinMapear = app(ContabilidadService::class)->cuentasSinMapear($empresa->id);
        $sinCodigo = app(\App\Services\SuperciasService::class)->cuentasSinCodigo($empresa->id);
        $total = \App\Models\AccountPlan::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count();

        return "{$total} cuentas · {$sinCodigo} sin código de Supercías · {$sinMapear} sin línea del estado";
    }
}
