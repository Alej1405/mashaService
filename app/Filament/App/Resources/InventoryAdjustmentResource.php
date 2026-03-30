<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\InventoryAdjustmentResource\Pages;
use App\Models\InventoryAdjustment;
use App\Models\InventoryItem;
use App\Models\ItemPresentation;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryAdjustmentResource extends Resource
{
    protected static ?string $model = InventoryAdjustment::class;

    protected static ?string $navigationIcon  = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel      = 'Ajuste de Inventario';
    protected static ?string $pluralModelLabel = 'Ajustes de Inventario';

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
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('item_presentation_id', null);
                    $set('factor_empaque', 1);
                    $set('cantidad_presentacion', null);
                }),

            Forms\Components\Select::make('tipo')
                ->label('Tipo de Ajuste')
                ->options([
                    'entrada'   => 'Entrada (incrementa stock)',
                    'salida'    => 'Salida (reduce stock)',
                    'correccion' => 'Corrección (establece stock exacto)',
                ])
                ->required()
                ->live(),

            Forms\Components\Select::make('item_presentation_id')
                ->label('Presentación / Empaque')
                ->helperText('Opcional. Si seleccionas una presentación, el factor de conversión se cargará automáticamente.')
                ->options(fn (Get $get) => $get('inventory_item_id')
                    ? ItemPresentation::where('inventory_item_id', $get('inventory_item_id'))
                        ->where('activo', true)
                        ->get()
                        ->mapWithKeys(fn ($p) => [$p->id => "{$p->nombre} (×{$p->factor_conversion})"])
                    : [])
                ->nullable()
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $p = ItemPresentation::find($state);
                        if ($p) {
                            $set('factor_empaque', (float) $p->factor_conversion);
                        }
                    } else {
                        $set('factor_empaque', 1);
                    }
                }),

            Forms\Components\TextInput::make('factor_empaque')
                ->label('Factor de Conversión')
                ->helperText('Unidades base por cada unidad de presentación. Se autocarga al seleccionar presentación.')
                ->numeric()
                ->default(1)
                ->minValue(0.000001)
                ->required()
                ->live(),

            Forms\Components\TextInput::make('cantidad_presentacion')
                ->label('Cantidad (en presentación/empaque)')
                ->helperText(fn (Get $get) => 'Total unidades base: ' . round((float)($get('cantidad_presentacion') ?? 0) * (float)($get('factor_empaque') ?? 1), 6))
                ->numeric()
                ->minValue(0.000001)
                ->required()
                ->live(),

            Forms\Components\TextInput::make('costo_unitario')
                ->label('Costo por Unidad Base')
                ->helperText('Se usa para generar el asiento contable en ajustes de entrada/salida.')
                ->numeric()
                ->prefix('$')
                ->nullable(),

            Forms\Components\Textarea::make('motivo')
                ->label('Motivo del Ajuste')
                ->required()
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('empresa_id', Filament::getTenant()->id)
                ->with(['inventoryItem', 'itemPresentation', 'user']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('inventoryItem.nombre')
                    ->label('Ítem')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'entrada',
                        'danger'  => 'salida',
                        'warning' => 'correccion',
                    ]),

                Tables\Columns\TextColumn::make('cantidad_presentacion')
                    ->label('Cant. Presentación')
                    ->numeric(decimalPlaces: 4),

                Tables\Columns\TextColumn::make('itemPresentation.nombre')
                    ->label('Presentación')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('total_unidades_base')
                    ->label('Unidades Base')
                    ->numeric(decimalPlaces: 4),

                Tables\Columns\TextColumn::make('stock_anterior')
                    ->label('Stock Ant.')
                    ->numeric(decimalPlaces: 4),

                Tables\Columns\TextColumn::make('stock_nuevo')
                    ->label('Stock Nuevo')
                    ->numeric(decimalPlaces: 4),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('motivo')
                    ->label('Motivo')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'entrada'    => 'Entrada',
                        'salida'     => 'Salida',
                        'correccion' => 'Corrección',
                    ]),
                Tables\Filters\SelectFilter::make('inventory_item_id')
                    ->label('Ítem')
                    ->options(fn () => InventoryItem::where('empresa_id', Filament::getTenant()->id)
                        ->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable(),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInventoryAdjustments::route('/'),
            'create' => Pages\CreateInventoryAdjustment::route('/create'),
        ];
    }
}
