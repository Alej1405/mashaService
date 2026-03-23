<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashRegisterResource\Pages;
use App\Models\CashRegister;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static ?string $navigationIcon       = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup      = 'Contabilidad General';
    protected static ?string $navigationParentItem = 'Caja';

    protected static ?string $tenantRelationshipName = 'cashRegisters';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('tipo')
                            ->options([
                                'principal' => 'Principal',
                                'chica'     => 'Caja Chica',
                            ])
                            ->required(),
                        Forms\Components\Placeholder::make('cuenta_contable')
                            ->label('Cuenta Contable Asignada')
                            ->content(fn($record) => $record?->accountPlan
                                ? $record->accountPlan->code . ' - ' . $record->accountPlan->name
                                : 'Se asignará automáticamente al guardar'
                            ),
                        Forms\Components\Toggle::make('activo')
                            ->default(true),
                        Forms\Components\Placeholder::make('saldo_actual')
                            ->content(fn ($record) => $record ? number_format($record->saldo_actual, 2) : '0.00'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'principal' => 'primary',
                        'chica'     => 'warning',
                    }),
                Tables\Columns\TextColumn::make('saldo_actual')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('accountPlan.code')
                    ->label('Cuenta')
                    ->description(fn ($record) => $record->accountPlan?->name),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'principal' => 'Principal',
                        'chica'     => 'Caja Chica',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
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
            'index' => Pages\ListCashRegisters::route('/'),
            'create' => Pages\CreateCashRegister::route('/create'),
            'edit' => Pages\EditCashRegister::route('/{record}/edit'),
        ];
    }
}
