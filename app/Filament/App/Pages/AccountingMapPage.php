<?php

namespace App\Filament\App\Pages;

use App\Models\AccountingMap;
use App\Models\AccountPlan;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class AccountingMapPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Contabilidad General';
    protected static ?string $navigationLabel = 'Mapeo Contable';
    protected static ?string $title = 'Mapeo Contable';

    protected static string $view = 'filament.app.pages.accounting-map-page';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin_empresa']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AccountingMap::query()->where('empresa_id', filament()->getTenant()->id))
            ->columns([
                TextColumn::make('tipo_item')
                    ->label('Tipo de Ítem')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('accountPlan.code')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('accountPlan.name')
                    ->label('Cuenta Contable')
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Select::make('account_plan_id')
                            ->label('Seleccionar Nueva Cuenta')
                            ->options(function () {
                                return AccountPlan::where('empresa_id', filament()->getTenant()->id)
                                    ->where('accepts_movements', true)
                                    ->get()
                                    ->mapWithKeys(fn ($plan) => [$plan->id => "{$plan->code} - {$plan->name}"]);
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->modalWidth('md')
                    ->modalHeading('Editar Mapeo Contable')
                    ->successNotificationTitle('Mapeo actualizado correctamente'),
            ]);
    }
}
