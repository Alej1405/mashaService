<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\PorcentajeRetencion;
use App\Models\TarifaIva;
use App\Services\ContabilidadService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $title           = 'Configuración tributaria';
    protected static ?int    $navigationSort  = 4;
    protected static string  $view            = 'filament.contabilidad.configuracion-tributaria';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    /** Valor de la mano de obra por hora: con él se calcula cada jornada de producción. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manoDeObra')
                ->label(fn () => 'Mano de obra: ' . (Filament::getTenant()->costo_hora_mano_obra !== null
                    ? '$ ' . number_format((float) Filament::getTenant()->costo_hora_mano_obra, 2, ',', '.') . ' por hora'
                    : 'sin definir'))
                ->icon('heroicon-o-clock')
                ->modalHeading('Mano de obra por hora')
                ->modalDescription('Cada jornada de producción guarda el valor vigente al iniciarse: cambiarlo no altera las jornadas ya registradas.')
                ->fillForm(fn () => ['costo_hora_mano_obra' => Filament::getTenant()->costo_hora_mano_obra])
                ->form([
                    TextInput::make('costo_hora_mano_obra')
                        ->label('Valor por hora')
                        ->numeric()->minValue(0.01)->step(0.01)->prefix('$')->suffix('por hora')
                        ->required(),
                ])
                ->action(function (array $data) {
                    Filament::getTenant()->update(['costo_hora_mano_obra' => $data['costo_hora_mano_obra']]);
                    Notification::make()->title('Mano de obra actualizada')->success()->send();
                }),
        ];
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
