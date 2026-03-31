<?php

namespace App\Filament\App\Resources\DebtResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AmortizationLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'amortizationLines';

    protected static ?string $title = 'Tabla de Amortización';

    protected static ?string $icon = 'heroicon-o-table-cells';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_cuota')
            ->columns([
                Tables\Columns\TextColumn::make('numero_cuota')
                    ->label('Cuota N°')
                    ->sortable()
                    ->weight('bold')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->estado === 'vencida' ? 'danger' : null),

                Tables\Columns\TextColumn::make('saldo_inicial')
                    ->label('Saldo Inicial')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('monto_capital')
                    ->label('Capital')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('monto_interes')
                    ->label('Intereses')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('seguro_desgravamen')
                    ->label('Desgravamen')
                    ->money('USD')
                    ->toggleable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_cuota')
                    ->label('Total Cuota')
                    ->money('USD')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('saldo_final')
                    ->label('Saldo Final')
                    ->money('USD'),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match ($state) {
                        'pendiente' => 'warning',
                        'pagada'    => 'success',
                        'vencida'   => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->paginated(false)
            ->striped()
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
