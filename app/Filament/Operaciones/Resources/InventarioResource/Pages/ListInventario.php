<?php

namespace App\Filament\Operaciones\Resources\InventarioResource\Pages;

use App\Filament\Operaciones\Resources\InventarioResource;
use App\Models\InventoryItem;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInventario extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registrar_movimiento')
                ->label('Registrar movimiento')
                ->icon('heroicon-o-arrows-right-left')
                ->url(fn (): string => \App\Filament\Operaciones\Pages\RegistrarMovimiento::getUrl()),
            Actions\CreateAction::make(),
        ];
    }

    /** Cuántos ítems y cuánto valen: lo que se pregunta antes de abrir la tabla. */
    public function getSubheading(): ?string
    {
        $resumen = InventoryItem::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()?->id)
            ->where('activo', true)
            ->selectRaw('count(*) as total, coalesce(sum(saldo_valorado), 0) as valor')
            ->first();

        return sprintf(
            '%d ítems · valor a costo promedio $ %s',
            $resumen->total,
            number_format((float) $resumen->valor, 2, ',', '.'),
        );
    }

    /**
     * Los tipos como pestañas con su conteo, igual que el diseño: en bodega se
     * filtra por lo que se busca, no abriendo un panel de filtros.
     */
    public function getTabs(): array
    {
        $conteos = InventoryItem::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()?->id)
            ->where('activo', true)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $tipos = [
            'materia_prima'      => 'Materia prima',
            'insumo'             => 'Insumos',
            'producto_terminado' => 'Producto terminado',
            'activo_fijo'        => 'Activos fijos',
            'servicio'           => 'Servicios',
        ];

        $tabs = ['todos' => Tab::make('Todos')->badge($conteos->sum())];

        foreach ($tipos as $tipo => $etiqueta) {
            if (! $conteos->has($tipo)) {
                continue;
            }

            $tabs[$tipo] = Tab::make($etiqueta)
                ->badge($conteos[$tipo])
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('type', $tipo));
        }

        return $tabs;
    }
}
