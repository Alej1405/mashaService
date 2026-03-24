<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreCustomerResource\Pages;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoreCustomerResource extends Resource
{
    protected static ?string $model = StoreCustomer::class;

    protected static ?string $tenantRelationshipName = 'storeCustomers';

    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationLabel  = 'Clientes';
    protected static ?string $navigationGroup  = 'E-Commerce';
    protected static ?string $modelLabel       = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int    $navigationSort   = 4;

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nombre')->label('Nombre')->required()->columnSpan(1),
            TextInput::make('apellido')->label('Apellido')->nullable()->columnSpan(1),
            TextInput::make('email')->label('Correo')->email()->required()->columnSpan(1),
            TextInput::make('telefono')->label('Teléfono')->nullable()->columnSpan(1),
            Toggle::make('activo')->label('Activo')->default(true)->columnSpan(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->formatStateUsing(fn ($record) =>
                        trim($record->nombre . ' ' . ($record->apellido ?? '')))
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Órdenes')
                    ->badge()
                    ->color('info'),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreCustomers::route('/'),
            'edit'  => Pages\EditStoreCustomer::route('/{record}/edit'),
        ];
    }
}
