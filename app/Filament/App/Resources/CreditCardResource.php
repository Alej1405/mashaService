<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CreditCardResource\Pages;
use App\Models\CreditCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CreditCardResource extends Resource
{
    protected static ?string $model = CreditCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Contabilidad General';

    protected static ?string $tenantRelationshipName = 'creditCards';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Tarjeta')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre Identificador')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ultimos_digitos')
                            ->label('Últimos 4 dígitos')
                            ->maxLength(4)
                            ->numeric(),
                        Forms\Components\Select::make('franquicia')
                            ->options([
                                'visa' => 'Visa',
                                'mastercard' => 'Mastercard',
                                'amex' => 'Amex',
                                'diners' => 'Diners',
                            ])
                            ->required(),
                        Forms\Components\Select::make('bank_id')
                            ->relationship('bank', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('limite_credito')
                            ->label('Límite de Crédito')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('saldo_utilizado')
                            ->numeric()
                            ->prefix('$')
                            ->readonly()
                            ->default(0),
                        Forms\Components\TextInput::make('dia_corte')
                            ->label('Día de Corte')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->required(),
                        Forms\Components\TextInput::make('dia_pago')
                            ->label('Día de Pago')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->required(),
                        Forms\Components\Placeholder::make('cuenta_contable')
                            ->label('Cuenta Contable Asignada')
                            ->content(fn($record) => $record?->accountPlan
                                ? $record->accountPlan->code . ' - ' . $record->accountPlan->name
                                : 'Se asignará automáticamente al guardar'
                            ),
                        Forms\Components\Toggle::make('activo')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank.nombre')
                    ->label('Banco')
                    ->sortable(),
                Tables\Columns\TextColumn::make('franquicia')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('limite_credito')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo_utilizado')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('saldo_disponible')
                    ->money('USD')
                    ->state(fn ($record) => $record->saldo_disponible),
                Tables\Columns\TextColumn::make('porcentaje_uso')
                    ->label('% Uso')
                    ->state(fn ($record) => $record->porcentaje_uso . '%')
                    ->badge()
                    ->color(fn ($state) => (float)$state > 90 ? 'danger' : ((float)$state > 70 ? 'warning' : 'success')),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank')
                    ->relationship('bank', 'nombre'),
                Tables\Filters\SelectFilter::make('franquicia')
                    ->options([
                        'visa' => 'Visa',
                        'mastercard' => 'Mastercard',
                        'amex' => 'Amex',
                        'diners' => 'Diners',
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
            'index' => Pages\ListCreditCards::route('/'),
            'create' => Pages\CreateCreditCard::route('/create'),
            'edit' => Pages\EditCreditCard::route('/{record}/edit'),
        ];
    }
}
