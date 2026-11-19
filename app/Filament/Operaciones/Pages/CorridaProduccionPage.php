<?php

namespace App\Filament\Operaciones\Pages;

use App\Models\StoreOrder;
use App\Modules\Produccion\Services\ConsolidacionSemielaborados;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * La corrida de producción: junta los pedidos pendientes, los consolida por
 * categoría y por semielaborado, y muestra qué preparar antes de ensamblar.
 *
 * El embudo: la producción ignora al cliente (solo importa para el despacho) y
 * agrupa el trabajo. El operador puede posponer un pedido («procesar luego») y
 * agendarlo para otra fecha: sale de la corrida de hoy. Generar las órdenes y el
 * despacho por cliente son los pasos siguientes.
 */
class CorridaProduccionPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-funnel';
    protected static ?string $navigationLabel = 'Corrida de producción';
    protected static ?string $title           = 'Corrida de producción';
    protected static ?string $navigationGroup = 'Producto';
    protected static ?int    $navigationSort  = 7;

    protected static string $view = 'filament.operaciones.corrida-produccion';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('produccion');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /** «Procesar luego»: agenda el pedido para otra fecha y lo saca de la corrida. */
    public function posponerAction(): Action
    {
        return Action::make('posponer')
            ->label('Procesar luego')
            ->modalHeading('¿Para cuándo lo dejamos?')
            ->modalSubmitActionLabel('Agendar')
            ->form([
                DatePicker::make('fecha')
                    ->label('Fecha de producción')
                    ->minDate(now()->addDay())
                    ->required(),
            ])
            ->action(function (array $arguments, array $data): void {
                $pedido = StoreOrder::find($arguments['pedido_id'] ?? null);
                if (! $pedido) {
                    return;
                }

                $pedido->update(['fecha_programada' => $data['fecha']]);

                Notification::make()
                    ->title('Pedido agendado')
                    ->body('Se procesará el ' . Carbon::parse($data['fecha'])->format('d/m/Y') . '.')
                    ->success()->send();
            });
    }

    /** Vuelve a meter un pedido agendado en la corrida de hoy. */
    public function retomarAction(): Action
    {
        return Action::make('retomar')
            ->label('Traer a hoy')
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                StoreOrder::find($arguments['pedido_id'] ?? null)?->update(['fecha_programada' => null]);
            });
    }

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $hoy     = now()->toDateString();

        $pendientes = StoreOrder::where('empresa_id', $empresa->id)
            ->whereIn('estado', ['pendiente', 'receptado'])
            ->with(['customer', 'orderItems.product.storeCategory'])
            ->orderBy('numero')
            ->get();

        $enCorrida = $pendientes->filter(
            fn (StoreOrder $p) => blank($p->fecha_programada) || $p->fecha_programada->toDateString() <= $hoy
        )->values();

        $agendados = $pendientes->filter(
            fn (StoreOrder $p) => filled($p->fecha_programada) && $p->fecha_programada->toDateString() > $hoy
        )->values();

        // Producción consolidada por categoría (ignora al cliente): categoría => [producto => cantidad].
        $porCategoria = [];
        foreach ($enCorrida as $pedido) {
            foreach ($pedido->orderItems as $item) {
                $cat    = $item->product?->storeCategory?->nombre ?? 'Sin categoría';
                $nombre = $item->product?->nombre ?? $item->nombre_snapshot;
                $porCategoria[$cat][$nombre] = ($porCategoria[$cat][$nombre] ?? 0) + (float) $item->cantidad;
            }
        }
        ksort($porCategoria);

        return [
            'enCorrida'      => $enCorrida,
            'agendados'      => $agendados,
            'porCategoria'   => $porCategoria,
            'semielaborados' => app(ConsolidacionSemielaborados::class)->paraPedidos($enCorrida),
        ];
    }
}
