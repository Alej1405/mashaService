<?php

namespace App\Filament\Contabilidad\Resources\ActivoFijoResource\Pages;

use App\Filament\Contabilidad\Resources\ActivoFijoResource;
use App\Models\ActivoFijo;
use App\Services\GastoService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListActivosFijos extends ListRecords
{
    protected static string $resource = ActivoFijoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('depreciar')
                ->label('Depreciar ' . now()->translatedFormat('F'))
                ->icon('heroicon-o-arrow-trending-down')
                ->requiresConfirmation()
                ->modalDescription('Calcula la cuota del mes de cada activo. Si ya se corrió este mes, no se duplica.')
                ->action(function () {
                    try {
                        $r = app(GastoService::class)->depreciarMes(Filament::getTenant()->id, now()->year, now()->month);

                        Notification::make()
                            ->title($r['activos'] ? "Depreciados {$r['activos']} activos" : 'Nada que depreciar este mes')
                            ->body($r['activos'] ? 'Total del mes: $ ' . number_format($r['total'], 2, ',', '.') : 'Ya estaba corrido o no hay activos con saldo.')
                            ->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('No se pudo depreciar')->body($e->getMessage())->danger()->persistent()->send();
                    }
                }),
            Actions\CreateAction::make()->label('Nuevo activo'),
        ];
    }

    public function getSubheading(): ?string
    {
        $empresa = Filament::getTenant();
        $activos = ActivoFijo::withoutGlobalScopes()->where('empresa_id', $empresa->id)->where('activo', true)->get();

        if ($activos->isEmpty()) {
            return 'Sin activos fijos registrados. Sin ellos no hay gasto por depreciación en el estado de resultados.';
        }

        return $activos->count() . ' activos · en libros $ '
            . number_format($activos->sum(fn ($a) => $a->valor_en_libros), 2, ',', '.')
            . ' · cuota mensual $ ' . number_format($activos->sum(fn ($a) => $a->cuota_mensual), 2, ',', '.');
    }
}
