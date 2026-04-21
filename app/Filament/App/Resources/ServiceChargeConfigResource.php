<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ServiceChargeConfigResource\Pages;
use App\Models\ServiceChargeConfig;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceChargeConfigResource extends Resource
{
    protected static ?string $model = ServiceChargeConfig::class;

    protected static ?string $tenantRelationshipName = 'serviceChargeConfigs';

    protected static ?string $navigationIcon   = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel  = 'Cargos Adicionales';
    protected static ?string $navigationGroup  = 'Diseño de Producto';
    protected static ?string $modelLabel       = 'Cargo adicional';
    protected static ?string $pluralModelLabel = 'Cargos adicionales';
    protected static ?int    $navigationSort   = 3;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Datos del cargo')->schema([
                TextInput::make('nombre')
                    ->label('Nombre del cargo')
                    ->required()
                    ->maxLength(150)
                    ->placeholder('Ej. Nacionalización, Fumigación, Transporte interno...')
                    ->columnSpan(2),

                Textarea::make('descripcion')
                    ->label('Descripción (opcional)')
                    ->rows(2)
                    ->maxLength(300)
                    ->columnSpanFull(),

                TextInput::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->required()
                    ->helperText('Para tipo "Por peso": monto por kg. Para tipo "Por trámite": monto fijo.')
                    ->columnSpan(1),

                Select::make('tipo')
                    ->label('Tipo de cobro')
                    ->options([
                        'tramite' => 'Por trámite (monto fijo)',
                        'peso'    => 'Por peso (monto × kg)',
                    ])
                    ->default('tramite')
                    ->required()
                    ->columnSpan(1),

                Select::make('iva_pct')
                    ->label('IVA')
                    ->options([
                        15 => '15% — Servicio',
                        0  => '0% — Impuesto / paso directo',
                    ])
                    ->default(15)
                    ->required()
                    ->columnSpan(1),

                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpan(1),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => $state === 'peso' ? 'warning' : 'info')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'peso'    => 'Por peso',
                        'tramite' => 'Por trámite',
                        default   => $state,
                    }),

                TextColumn::make('iva_pct')
                    ->label('IVA')
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'gray' : 'success')
                    ->formatStateUsing(fn ($state) => $state . '%'),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServiceChargeConfigs::route('/'),
            'create' => Pages\CreateServiceChargeConfig::route('/create'),
            'edit'   => Pages\EditServiceChargeConfig::route('/{record}/edit'),
        ];
    }
}
