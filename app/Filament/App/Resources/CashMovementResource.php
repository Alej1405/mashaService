<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashMovementResource\Pages;
use App\Models\CashMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashMovementResource extends Resource
{
    protected static ?string $model = CashMovement::class;

    protected static ?string $navigationIcon       = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup      = 'Contabilidad General';
    protected static ?string $navigationParentItem = 'Caja';

    protected static ?string $tenantRelationshipName = 'cashMovements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Movimiento de Caja')
                    ->schema([
                        Forms\Components\Select::make('cash_register_id')
                            ->label('Caja')
                            ->relationship('cashRegister', 'nombre')
                            ->disabled(),
                        Forms\Components\Select::make('cash_session_id')
                            ->label('Sesión')
                            ->relationship('cashSession', 'id')
                            ->disabled(),
                        Forms\Components\TextInput::make('tipo')
                            ->disabled(),
                        Forms\Components\TextInput::make('monto')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        Forms\Components\TextInput::make('descripcion')
                            ->columnSpanFull()
                            ->disabled(),
                        Forms\Components\Select::make('journal_entry_id')
                            ->label('Asiento Contable')
                            ->relationship('journalEntry', 'numero')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([])
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cashRegister.nombre')
                    ->label('Caja')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ingreso' => 'success',
                        'egreso'  => 'danger',
                    }),
                Tables\Columns\TextColumn::make('monto')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('journalEntry.numero')
                    ->label('Asiento'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'ingreso' => 'Ingreso',
                        'egreso' => 'Egreso',
                    ]),
                Tables\Filters\SelectFilter::make('cash_register_id')
                    ->label('Caja')
                    ->relationship('cashRegister', 'nombre'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashMovements::route('/'),
            'view'  => Pages\ViewCashMovement::route('/{record}'),
        ];
    }
}
