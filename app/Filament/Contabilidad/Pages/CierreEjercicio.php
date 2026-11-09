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
    protected static ?string $navigationGroup = 'Informes y cierre';
    protected static ?string $title           = 'Cierre de ejercicio';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.contabilidad.cierre-ejercicio';

    /** Año que se mira: los anteriores al inicio de gestión se cierran mes a mes aquí. */
    public int $anio;

    public function mount(): void
    {
        $this->anio = now()->year;
    }

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
                ->visible(fn () => $this->anio === now()->year)
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

    /** Cierra un mes anterior al inicio de gestión que ya quedó cuadrado. */
    public function cerrarMesAction(): Action
    {
        return Action::make('cerrarMes')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => sprintf('Cerrar %02d/%d', $arguments['mes'] ?? 0, $this->anio))
            ->modalDescription('Se comprueba que el 104 del mes esté registrado y que no haya asientos descuadrados. '
                . 'Cerrado, el mes no admite movimientos salvo con el modo cuadre encendido.')
            ->modalSubmitActionLabel('Cerrar el mes')
            ->action(function (array $arguments) {
                try {
                    app(ContabilidadService::class)->cerrarMesCuadre(Filament::getTenant()->id, $this->anio, (int) $arguments['mes']);
                    Notification::make()->title(sprintf('%02d/%d cerrado', $arguments['mes'], $this->anio))->success()->send();
                } catch (\Throwable $ex) {
                    Notification::make()->title('No se cerró el mes')->body($ex->getMessage())->danger()->persistent()->send();
                }
            });
    }

    /** Reabre un mes cerrado a mano en el cuadre, por si se cerró antes de tiempo. */
    public function reabrirMesAction(): Action
    {
        return Action::make('reabrirMes')
            ->requiresConfirmation()
            ->color('gray')
            ->modalHeading(fn (array $arguments) => sprintf('Reabrir %02d/%d', $arguments['mes'] ?? 0, $this->anio))
            ->modalDescription('Solo para un mes cerrado a mano durante el cuadre. Los cerrados por el 104 no se reabren.')
            ->modalSubmitActionLabel('Reabrir')
            ->action(function (array $arguments) {
                $empresaId = Filament::getTenant()->id;
                $periodo = \App\Models\PeriodoContable::withoutGlobalScopes()->where('empresa_id', $empresaId)
                    ->where('anio', $this->anio)->where('mes', (int) $arguments['mes'])->first();

                if ($periodo?->motivo !== 'cuadre') {
                    Notification::make()->title('Ese mes no se cerró en el cuadre')->danger()->send();

                    return;
                }

                app(ContabilidadService::class)->reabrirMes($empresaId, $this->anio, (int) $arguments['mes']);
                Notification::make()->title(sprintf('%02d/%d reabierto', $arguments['mes'], $this->anio))->success()->send();
            });
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $anio = $this->anio;
        $inicio = $empresa->inicio_gestion_erp?->copy()->startOfMonth();
        $ultimoMes = $anio === now()->year ? now()->month : 12;

        // Asientos y sumas mes a mes del ejercicio en curso.
        $meses = JournalEntry::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->whereYear('fecha', $anio)
            ->where('status', 'confirmado')
            ->groupBy(DB::raw('extract(month from fecha)'))
            ->selectRaw('extract(month from fecha) as mes, count(*) as asientos, sum(total_debe) as debe, sum(total_haber) as haber')
            ->orderBy('mes')
            ->get()
            ->keyBy(fn ($m) => (int) $m->mes);

        $periodos = \App\Models\PeriodoContable::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)->where('anio', $anio)->get()->keyBy('mes');

        // Los años con asientos, para elegir cuál se mira.
        $anios = JournalEntry::withoutGlobalScopes()->where('empresa_id', $empresa->id)
            ->selectRaw('distinct extract(year from fecha)::int as anio')->orderByDesc('anio')
            ->pluck('anio')->push(now()->year)->unique()->sortDesc()->values();

        return [
            'anio'         => $anio,
            'anios'        => $anios,
            'ultimoMes'    => $ultimoMes,
            'inicio'       => $inicio,
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
