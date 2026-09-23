<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\PorcentajeRetencion;
use App\Models\TarifaIva;
use App\Services\ContabilidadService;
use Filament\Facades\Filament;
use Filament\Pages\Page;

/**
 * Tarifas de IVA y porcentajes de retención, con su vigencia.
 *
 * Un comprobante se calcula con lo que regía el día de su fecha. Por eso aquí
 * no se edita un número suelto: se abre y se cierra un periodo de vigencia.
 */
class ConfiguracionTributaria extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Configuración tributaria';
    protected static ?string $title           = 'Configuración tributaria';
    protected static ?int    $navigationSort  = 7;
    protected static string  $view            = 'filament.contabilidad.configuracion-tributaria';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $conta   = app(ContabilidadService::class);

        return [
            'empresa'      => $empresa,
            'tarifas'      => TarifaIva::query()
                                ->where(fn ($q) => $q->where('empresa_id', $empresa->id)->orWhereNull('empresa_id'))
                                ->orderByDesc('vigente_desde')->get(),
            'retenciones'  => PorcentajeRetencion::query()
                                ->where(fn ($q) => $q->where('empresa_id', $empresa->id)->orWhereNull('empresa_id'))
                                ->orderBy('tipo')->orderByDesc('vigente_desde')->get()->groupBy('tipo'),
            'clasificacion'=> $conta->clasificacion($empresa->id),
            'societario'   => $conta->resumenSocietario($empresa->id),
        ];
    }
}
