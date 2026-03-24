<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreCouponResource\Pages;
use App\Models\StoreCoupon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoreCouponResource extends Resource
{
    protected static ?string $model = StoreCoupon::class;

    protected static ?string $tenantRelationshipName = 'storeCoupons';

    protected static ?string $navigationIcon   = 'heroicon-o-ticket';
    protected static ?string $navigationLabel  = 'Cupones';
    protected static ?string $navigationGroup  = 'E-Commerce';
    protected static ?string $modelLabel       = 'Cupón';
    protected static ?string $pluralModelLabel = 'Cupones';
    protected static ?int    $navigationSort   = 5;

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
            TextInput::make('codigo')
                ->label('Código')
                ->required()
                ->maxLength(50)
                ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                ->columnSpan(1),
            Select::make('tipo')
                ->label('Tipo')
                ->options([
                    'porcentaje' => 'Porcentaje (%)',
                    'monto_fijo' => 'Monto Fijo ($)',
                ])
                ->required()
                ->columnSpan(1),
            TextInput::make('valor')
                ->label('Valor')
                ->numeric()
                ->required()
                ->columnSpan(1),
            TextInput::make('minimo_compra')
                ->label('Mínimo de Compra')
                ->numeric()
                ->nullable()
                ->prefix('$')
                ->columnSpan(1),
            TextInput::make('maximo_usos')
                ->label('Máximo de Usos')
                ->numeric()
                ->nullable()
                ->helperText('Vacío = ilimitado')
                ->columnSpan(1),
            Toggle::make('activo')
                ->label('Activo')
                ->default(true)
                ->columnSpan(1),
            DatePicker::make('fecha_inicio')
                ->label('Válido desde')
                ->nullable()
                ->columnSpan(1),
            DatePicker::make('fecha_fin')
                ->label('Válido hasta')
                ->nullable()
                ->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'porcentaje' ? 'Porcentaje' : 'Monto Fijo'),
                TextColumn::make('valor')
                    ->label('Valor')
                    ->formatStateUsing(fn ($record) =>
                        $record->tipo === 'porcentaje'
                            ? "{$record->valor}%"
                            : '$' . number_format($record->valor, 2)),
                TextColumn::make('usos_actuales')
                    ->label('Usos')
                    ->formatStateUsing(fn ($record) =>
                        $record->maximo_usos
                            ? "{$record->usos_actuales} / {$record->maximo_usos}"
                            : "{$record->usos_actuales} / ∞"),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('fecha_fin')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('Sin vencimiento'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStoreCoupons::route('/'),
            'create' => Pages\CreateStoreCoupon::route('/create'),
            'edit'   => Pages\EditStoreCoupon::route('/{record}/edit'),
        ];
    }
}
