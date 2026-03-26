<?php

namespace App\Filament\App\Resources\DebtResource\Pages;

use App\Filament\App\Resources\DebtResource;
use App\Models\Debt;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDebts extends ListRecords
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // Usamos el query base del recurso (ya tiene el scope de empresa)
        $base = static::getResource()::getEloquentQuery();

        return [
            'todas' => Tab::make('Todas')
                ->badge((clone $base)->count()),

            'activas' => Tab::make('Activas / Parciales')
                ->badge((clone $base)->whereIn('estado', ['activa', 'parcial'])->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('estado', ['activa', 'parcial'])),

            'vencidas' => Tab::make('Vencidas')
                ->badge((clone $base)->where('estado', 'vencida')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('estado', 'vencida')),

            'pagadas' => Tab::make('Pagadas')
                ->badge((clone $base)->where('estado', 'pagada')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('estado', 'pagada')),

            'borrador' => Tab::make('Borradores')
                ->badge((clone $base)->where('estado', 'borrador')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('estado', 'borrador')),
        ];
    }
}
