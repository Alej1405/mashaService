<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BankAccountResource\Pages;
use App\Models\BankAccount;
use App\Models\Bank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $tenantRelationshipName = 'bankAccounts';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Contabilidad General';
    protected static ?string $modelLabel = 'Cuenta Bancaria';
    protected static ?string $pluralModelLabel = 'Cuentas Bancarias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_id')
                    ->label('Banco')
                    ->options(Bank::where('activo', true)->get()->pluck('nombre', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('numero_cuenta')
                    ->label('Número de Cuenta')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('tipo_cuenta')
                    ->label('Tipo de Cuenta')
                    ->options([
                        'corriente' => 'Corriente',
                        'ahorros' => 'Ahorros',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('nombre_titular')
                    ->label('Nombre del Titular')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Placeholder::make('cuenta_contable')
                    ->label('Cuenta Contable Asignada')
                    ->content(fn($record) => $record?->accountPlan
                        ? $record->accountPlan->code . ' - ' . $record->accountPlan->name
                        : 'Se asignará automáticamente al guardar'
                    ),
                Forms\Components\TextInput::make('saldo_inicial')
                    ->label('Saldo Inicial')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank.nombre')
                    ->label('Banco')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('numero_cuenta')
                    ->label('Número')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_cuenta')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => $state === 'corriente' ? 'primary' : 'success'),
                Tables\Columns\TextColumn::make('nombre_titular')
                    ->label('Titular')
                    ->searchable(),
                Tables\Columns\TextColumn::make('accountPlan.name')
                    ->label('Cuenta Contable')
                    ->description(fn ($record) => $record->accountPlan?->code),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank_id')
                    ->label('Banco')
                    ->relationship('bank', 'nombre'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
