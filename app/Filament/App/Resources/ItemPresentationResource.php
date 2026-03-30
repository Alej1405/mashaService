<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ItemPresentationResource\Pages;
use App\Models\InventoryItem;
use App\Models\ItemPresentation;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ItemPresentationResource extends Resource
{
    protected static ?string $model = ItemPresentation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-cube-transparent';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel      = 'Presentación / Empaque';
    protected static ?string $pluralModelLabel = 'Presentaciones / Empaques';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('inventory_item_id')
                ->label('Ítem de Inventario')
                ->options(fn () => InventoryItem::where('empresa_id', Filament::getTenant()->id)
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id'))
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('nombre')
                ->label('Nombre de la Presentación')
                ->placeholder('Ej: Caja x12, Paquete x6, Bulto x50')
                ->required()
                ->maxLength(150),

            Forms\Components\TextInput::make('factor_conversion')
                ->label('Factor de Conversión')
                ->helperText('Cuántas unidades base contiene esta presentación. Ej: una "Caja x12" tiene factor 12.')
                ->numeric()
                ->default(1)
                ->minValue(0.000001)
                ->required(),

            Forms\Components\Toggle::make('es_unidad_base')
                ->label('Es la unidad base del ítem')
                ->helperText('Marcar si esta presentación equivale a 1 unidad de stock.'),

            Forms\Components\Toggle::make('activo')
                ->label('Activo')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('inventoryItem', fn ($q) =>
                $q->where('empresa_id', Filament::getTenant()->id)
            ))
            ->columns([
                Tables\Columns\TextColumn::make('inventoryItem.nombre')
                    ->label('Ítem')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Presentación')
                    ->searchable(),

                Tables\Columns\TextColumn::make('factor_conversion')
                    ->label('Factor')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\IconColumn::make('es_unidad_base')
                    ->label('Base')
                    ->boolean(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('inventory_item_id')
                    ->label('Ítem')
                    ->options(fn () => InventoryItem::where('empresa_id', Filament::getTenant()->id)
                        ->where('activo', true)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id'))
                    ->searchable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListItemPresentations::route('/'),
            'create' => Pages\CreateItemPresentation::route('/create'),
            'edit'   => Pages\EditItemPresentation::route('/{record}/edit'),
        ];
    }
}
