<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AlmacenResource\Pages;
use App\Filament\App\Resources\AlmacenResource\RelationManagers;
use App\Models\Almacen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlmacenResource extends Resource
{
    protected static ?string $model = Almacen::class;

    protected static ?string $tenantRelationshipName = 'almacenes';

    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Almacenes';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel      = 'Almacén';
    protected static ?string $pluralModelLabel = 'Almacenes';
    protected static ?int    $navigationSort  = 10;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Almacén')
                ->schema([
                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),
                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de Almacén')
                        ->options(Almacen::tiposLabels())
                        ->required()
                        ->default('bodega_propia'),
                    Forms\Components\TextInput::make('responsable')
                        ->label('Responsable / Bodeguero')
                        ->maxLength(150),
                    Forms\Components\TextInput::make('direccion')
                        ->label('Dirección / Ubicación Física')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Almacen::tiposLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('responsable')
                    ->label('Responsable')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('zonas_count')
                    ->label('Zonas')
                    ->counts('zonas')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean()
                    ->label('Activo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options(Almacen::tiposLabels()),
                Tables\Filters\TernaryFilter::make('activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ZonasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAlmacenes::route('/'),
            'create' => Pages\CreateAlmacen::route('/create'),
            'edit'   => Pages\EditAlmacen::route('/{record}/edit'),
        ];
    }
}
