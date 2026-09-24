<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\CatalogoSupercias;
use App\Services\MapeoSuperciasService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Revisión del mapeo a la Superintendencia.
 *
 * Todas las cuentas tienen ya su línea del estado: el sistema la asigna sola al
 * crearlas. Aquí solo aparecen aquellas en las que no estuvo seguro, con la
 * propuesta puesta y las alternativas ordenadas. El contador confirma o cambia,
 * y lo que toca queda marcado para que el automático no vuelva a moverlo.
 */
class RevisionSupercias extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'Revisar mapeo SCVS';
    protected static ?string $title           = 'Revisión del mapeo a la Superintendencia';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.contabilidad.revision-supercias';

    /** cuenta_id => código elegido */
    public array $eleccion = [];

    public bool $verTodas = false;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function getNavigationBadge(): ?string
    {
        $empresa = Filament::getTenant();

        if (! $empresa) {
            return null;
        }

        $porRevisar = app(MapeoSuperciasService::class)->porRevisar($empresa->id);

        return $porRevisar > 0 ? (string) $porRevisar : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        $this->cargar();
    }

    private function cargar(): void
    {
        $this->eleccion = app(MapeoSuperciasService::class)
            ->paraRevisar(Filament::getTenant()->id, ! $this->verTodas)
            ->mapWithKeys(fn ($r) => [$r['cuenta']->id => $r['codigo']])
            ->all();
    }

    public function alternar(): void
    {
        $this->verTodas = ! $this->verTodas;
        $this->cargar();
    }

    /** Confirma lo que hay en pantalla: lo propuesto o lo que el contador cambió. */
    public function confirmar(): void
    {
        $aplicados = app(MapeoSuperciasService::class)
            ->aplicar(Filament::getTenant()->id, $this->eleccion);

        $this->cargar();

        Notification::make()
            ->title("{$aplicados} cuentas confirmadas")
            ->body('Quedan marcadas como revisadas: el mapeo automático ya no las toca.')
            ->success()->send();
    }

    /** Confirma una sola fila, para ir de a poco. */
    public function confirmarUna(int $cuentaId): void
    {
        $codigo = $this->eleccion[$cuentaId] ?? null;

        if (! $codigo) {
            return;
        }

        app(MapeoSuperciasService::class)->aplicar(Filament::getTenant()->id, [$cuentaId => $codigo]);
        $this->cargar();

        Notification::make()->title('Cuenta confirmada')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rehacer')
                ->label('Volver a proponer')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Vuelve a calcular la propuesta de las cuentas que nadie ha '
                    . 'confirmado todavía. Lo que ya revisaste no se toca.')
                ->action(function () {
                    $r = app(MapeoSuperciasService::class)
                        ->mapearTodo(Filament::getTenant()->id, incluirRevisadas: true);

                    $this->cargar();

                    Notification::make()
                        ->title("{$r['asignadas']} cuentas vueltas a proponer")
                        ->body($r['sin_mapear'] > 0
                            ? "{$r['sin_mapear']} se quedaron sin línea: avísame."
                            : 'Ninguna quedó sin línea del estado.')
                        ->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $servicio = app(MapeoSuperciasService::class);

        return [
            'cuentas'   => $servicio->paraRevisar($empresa->id, ! $this->verTodas),
            'porRevisar' => $servicio->porRevisar($empresa->id),
            'total'     => \App\Models\AccountPlan::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)->where('accepts_movements', true)->count(),
            'sinMapear' => app(\App\Services\SuperciasService::class)->cuentasSinCodigo($empresa->id),
            'catalogo'  => CatalogoSupercias::whereIn('estado', ['situacion_financiera', 'resultado_integral'])
                ->whereNotNull('nombre')->orderBy('codigo')
                ->get(['codigo', 'nombre'])
                ->mapWithKeys(fn ($l) => [$l->codigo => "{$l->codigo} · {$l->nombre}"]),
        ];
    }
}
