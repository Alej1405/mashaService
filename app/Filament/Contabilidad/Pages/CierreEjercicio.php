<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\EjercicioContable;
use App\Models\JournalEntry;
use App\Services\ContabilidadService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Cierre de ejercicio.
 *
 * Lo presentado a la Superintendencia no se vuelve a tocar: cerrar bloquea el
 * año y deja escrito el resultado que se llevó a patrimonio.
 */
class CierreEjercicio extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'Cierre de ejercicio';
    protected static ?string $title           = 'Cierre de ejercicio';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.contabilidad.cierre-ejercicio';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cerrar')
                ->label('Cerrar el ejercicio ' . now()->year)
                ->icon('heroicon-o-lock-closed')
                ->requiresConfirmation()
                ->modalHeading('Cerrar el ejercicio ' . now()->year)
                ->modalDescription('Después de cerrarlo no se podrán registrar asientos con fecha de este año. Se comprueba que todo cuadre antes de bloquear.')
                ->modalSubmitActionLabel('Cerrar el ejercicio')
                ->action(function () {
                    try {
                        $e = app(ContabilidadService::class)->cerrarEjercicio(Filament::getTenant()->id, now()->year);

                        Notification::make()
                            ->title('Ejercicio ' . $e->anio . ' cerrado')
                            ->body('Resultado del ejercicio: $ ' . number_format((float) $e->resultado, 2, ',', '.'))
                            ->success()->send();
                    } catch (\Throwable $ex) {
                        Notification::make()->title('No se pudo cerrar')->body($ex->getMessage())
                            ->danger()->persistent()->send();
                    }
                }),
        ];
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $anio = now()->year;

        // Asientos y sumas mes a mes del ejercicio en curso.
        $meses = JournalEntry::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->whereYear('fecha', $anio)
            ->where('status', 'confirmado')
            ->groupBy(DB::raw('extract(month from fecha)'))
            ->selectRaw('extract(month from fecha) as mes, count(*) as asientos, sum(total_debe) as debe, sum(total_haber) as haber')
            ->orderBy('mes')
            ->get();

        $periodos = \App\Models\PeriodoContable::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)->where('anio', $anio)->get()->keyBy('mes');

        return [
            'anio'         => $anio,
            'meses'        => $meses,
            'periodos'     => $periodos,
            'ejercicios'   => EjercicioContable::withoutGlobalScopes()
                                ->where('empresa_id', $empresa->id)->orderByDesc('anio')->get(),
            'descuadrados' => JournalEntry::withoutGlobalScopes()
                                ->where('empresa_id', $empresa->id)->whereYear('fecha', $anio)
                                ->where('status', 'confirmado')->where('esta_cuadrado', false)->count(),
            'saldos'       => app(ContabilidadService::class)->saldos($empresa->id, "{$anio}-12-31", $anio),
        ];
    }
}
