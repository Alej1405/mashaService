<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ProductionOrderResource\Pages;
use App\Models\ProductionOrder;
use App\Models\InventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Closure;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Filament\App\Resources\InventoryItemResource;

class ProductionOrderResource extends Resource
{
    protected static ?string $model = ProductionOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    
    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Orden de Producción';
    
    protected static ?string $pluralModelLabel = 'Órdenes de Producción';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Producto a Fabricar')
                        ->schema([
                            Select::make('inventory_item_id')
                                ->label('Producto Terminado')
                                ->relationship('finishedProduct', 'nombre', fn ($query) => $query->where('type', 'producto_terminado'))
                                ->searchable()
                                ->preload()
                                ->createOptionForm(fn () => InventoryItemResource::getQuickCreateFormSchema())
                                ->required(),
                            TextInput::make('cantidad_producida')
                                ->label('Cantidad a Producir')
                                ->numeric()
                                ->minValue(0.0001)
                                ->required(),
                            DatePicker::make('fecha')
                                ->label('Fecha')
                                ->default(now())
                                ->required(),
                        ]),
                    Wizard\Step::make('Consumo de Materiales')
                        ->schema([
                            Repeater::make('materials')
                                ->relationship()
                                ->schema([
                                    Select::make('inventory_item_id')
                                        ->label('Materia Prima / Insumo')
                                        ->relationship('inventoryItem', 'nombre', fn ($query) => $query->whereIn('type', ['materia_prima', 'insumo']))
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm(fn () => InventoryItemResource::getQuickCreateFormSchema())
                                        ->required()
                                        ->live()
                                        ->hint(function (Get $get) {
                                            $itemId = $get('inventory_item_id');
                                            if (!$itemId) return null;
                                            
                                            $item = InventoryItem::find($itemId);
                                            if (!$item) return null;

                                            $unidad = $item->measurementUnit?->abreviatura ?? '';
                                            return "Stock: {$item->stock_actual} {$unidad}";
                                        })
                                        ->hintColor(function (Get $get) {
                                            $itemId = $get('inventory_item_id');
                                            if (!$itemId) return null;
                                            
                                            $item = InventoryItem::find($itemId);
                                            if (!$item) return null;

                                            return $item->stock_actual <= ($item->stock_minimo ?? 0) ? 'danger' : 'success';
                                        })
                                        ->hintIcon(function (Get $get) {
                                            $itemId = $get('inventory_item_id');
                                            if (!$itemId) return null;
                                            
                                            $item = InventoryItem::find($itemId);
                                            if (!$item) return null;

                                            return $item->stock_actual <= ($item->stock_minimo ?? 0) ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle';
                                        })
                                        ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                            $set('costo_unitario', InventoryItem::find($state)?->purchase_price ?? 0)
                                        ),
                                    TextInput::make('cantidad_consumida')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.0001)
                                        ->rules([
                                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                $itemId = $get('inventory_item_id');
                                                if (!$itemId) return;

                                                $item = InventoryItem::find($itemId);
                                                $stockDisponible = $item?->stock_actual ?? 0;

                                                if ($value > $stockDisponible) {
                                                    $fail("Stock insuficiente. Solo tienes {$stockDisponible} unidades disponibles de {$item->nombre}.");
                                                }
                                            },
                                        ]),
                                    TextInput::make('costo_unitario')
                                        ->label('Costo Unitario')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly(),
                                ])
                                ->columns(3)
                                ->itemLabel(fn (array $state): ?string => InventoryItem::find($state['inventory_item_id'] ?? null)?->nombre ?? null),
                        ]),
                    Wizard\Step::make('Resumen / Notas')
                        ->schema([
                            Textarea::make('notas')
                                ->label('Notas de Producción')
                                ->rows(3),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('referencia')
                    ->label('Ref.')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('finishedProduct.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('cantidad_producida')
                    ->label('Cant.')
                    ->numeric(4),
                TextColumn::make('costo_total')
                    ->label('Costo Total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'borrador' => 'gray',
                        'completado' => 'success',
                        'anulado' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'completado' => 'Completado',
                        'anulado' => 'Anulado',
                    ]),
                Tables\Filters\Filter::make('fecha')
                    ->form([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['desde'], fn ($q) => $q->whereDate('fecha', '>=', $data['desde']))
                        ->when($data['hasta'], fn ($q) => $q->whereDate('fecha', '<=', $data['hasta']))
                    ),
            ])
            ->actions([
                Action::make('completar')
                    ->label('Completar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Completar Producción?')
                    ->modalDescription('Esto generará los movimientos de inventario y el asiento contable automáticamente. Esta acción no se puede deshacer.')
                    ->visible(fn ($record) => $record->estado === 'borrador')
                    ->action(function (ProductionOrder $record) {
                        try {
                            $record->update(['estado' => 'completado']);
                            
                            Notification::make()
                                ->title('Producción Completada')
                                ->success()
                                ->body("Se ha procesado el inventario y la contabilidad para la orden {$record->referencia}")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error en Procesamiento')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->estado === 'borrador'),
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
            'index' => Pages\ListProductionOrders::route('/'),
            'create' => Pages\CreateProductionOrder::route('/create'),
            'edit' => Pages\EditProductionOrder::route('/{record}/edit'),
        ];
    }
}
