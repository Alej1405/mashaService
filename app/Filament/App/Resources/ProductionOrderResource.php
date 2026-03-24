<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ProductionOrderResource\Pages;
use App\Models\ProductDesign;
use App\Models\ProductPresentation;
use App\Models\ProductionOrder;
use App\Models\InventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ProductionOrderResource extends Resource
{
    protected static ?string $model = ProductionOrder::class;
    protected static ?string $tenantRelationshipName = 'productionOrders';

    protected static ?string $navigationIcon  = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel      = 'Orden de Producción';
    protected static ?string $pluralModelLabel = 'Órdenes de Producción';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([

                // ── Paso 1: Qué producir ─────────────────────────────
                Wizard\Step::make('Producto a Fabricar')
                    ->schema([

                        // Selector de Diseño (solo UI, no se guarda)
                        Select::make('_product_design_id')
                            ->label('Diseño de Producto')
                            ->options(fn () => ProductDesign::where('empresa_id', \Filament\Facades\Filament::getTenant()->id)
                                ->where('activo', true)
                                ->pluck('nombre', 'id'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (Set $set) {
                                $set('product_presentation_id', null);
                                $set('materials', []);
                            })
                            ->placeholder('Seleccionar diseño...')
                            ->columnSpan(2),

                        // Presentación filtrada por diseño
                        Select::make('product_presentation_id')
                            ->label('Presentación')
                            ->options(function (Get $get) {
                                $designId = $get('_product_design_id');
                                if (!$designId) return [];
                                return ProductPresentation::where('product_design_id', $designId)
                                    ->where('activa', true)
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [
                                        $p->id => $p->nombre
                                            . ' — lote base: ' . rtrim(rtrim(number_format($p->cantidad_minima_produccion, 4), '0'), '.') . ' u.',
                                    ]);
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                self::poblarMateriales($get, $set);
                            })
                            ->placeholder('Primero selecciona un diseño...')
                            ->columnSpan(2),

                        // Producto terminado en inventario
                        Select::make('inventory_item_id')
                            ->label('Producto Terminado (Inventario)')
                            ->relationship('finishedProduct', 'nombre', fn ($query) => $query->where('type', 'producto_terminado'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm(fn () => InventoryItemResource::getQuickCreateFormSchema())
                            ->createOptionUsing(function (array $data): int {
                                return InventoryItem::create([
                                    ...$data,
                                    'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                    'activo'     => true,
                                ])->getKey();
                            })
                            ->required()
                            ->helperText('Ítem de inventario que se incrementará al completar la orden.')
                            ->columnSpan(2),

                        // Cantidad a producir
                        TextInput::make('cantidad_producida')
                            ->label('Cantidad a Producir')
                            ->numeric()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::poblarMateriales($get, $set);
                            })
                            ->columnSpan(1),

                        DatePicker::make('fecha')
                            ->label('Fecha')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),

                        // Resumen de la fórmula escalada
                        Placeholder::make('_resumen_formula')
                            ->label('Vista previa de materiales')
                            ->columnSpanFull()
                            ->content(function (Get $get) {
                                $presentationId = $get('product_presentation_id');
                                $cantidad       = (float) ($get('cantidad_producida') ?? 0);

                                if (!$presentationId || $cantidad <= 0) {
                                    return new HtmlString('<p class="text-sm text-gray-400">Selecciona una presentación e ingresa la cantidad para ver la fórmula escalada.</p>');
                                }

                                $presentation = ProductPresentation::with('formulaLines.inventoryItem', 'formulaLines.measurementUnit')->find($presentationId);
                                if (!$presentation || $presentation->formulaLines->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-400">Esta presentación no tiene fórmula definida.</p>');
                                }

                                $lote = max((float) $presentation->cantidad_minima_produccion, 0.0001);
                                $fmt  = fn (float $n): string => rtrim(rtrim(number_format($n, 4), '0'), '.');

                                $rows = '';
                                foreach ($presentation->formulaLines as $line) {
                                    if (!$line->inventory_item_id) continue;
                                    $nombre       = $line->inventoryItem?->nombre ?? '—';
                                    $unidad       = $line->measurementUnit?->abreviatura ?? '';
                                    $cantBase     = (float) $line->cantidad;
                                    $cantEscalada = ($cantBase * $cantidad) / $lote;

                                    $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                        <td class='py-1 pr-4 text-sm'>{$nombre}</td>
                                        <td class='py-1 pr-4 text-sm text-right text-gray-400'>" . $fmt($cantBase) . " {$unidad}</td>
                                        <td class='py-1 text-sm text-right font-semibold'>" . $fmt($cantEscalada) . " {$unidad}</td>
                                    </tr>";
                                }

                                return new HtmlString("
                                    <div class='overflow-x-auto'>
                                        <p class='text-xs text-gray-400 mb-2'>Lote base: {$fmt($lote)} u. → Producir: {$fmt($cantidad)} u. (factor: " . $fmt($cantidad / $lote) . "×)</p>
                                        <table class='w-full'>
                                            <thead>
                                                <tr class='border-b border-gray-200 dark:border-gray-600'>
                                                    <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Insumo</th>
                                                    <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>x Lote base</th>
                                                    <th class='pb-1 text-right text-xs font-semibold text-gray-500 uppercase'>Cantidad necesaria</th>
                                                </tr>
                                            </thead>
                                            <tbody>{$rows}</tbody>
                                        </table>
                                    </div>
                                ");
                            }),
                    ])->columns(6),

                // ── Paso 2: Materiales (auto-poblados, editables) ────
                Wizard\Step::make('Consumo de Materiales')
                    ->schema([
                        Repeater::make('materials')
                            ->relationship()
                            ->label('Materiales a consumir')
                            ->schema([
                                Select::make('inventory_item_id')
                                    ->label('Insumo / Materia Prima')
                                    ->relationship('inventoryItem', 'nombre', fn ($query) => $query->whereIn('type', ['materia_prima', 'insumo']))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->hint(function (Get $get) {
                                        $item = InventoryItem::find($get('inventory_item_id'));
                                        if (!$item) return null;
                                        $unidad = $item->measurementUnit?->abreviatura ?? '';
                                        return "Stock: {$item->stock_actual} {$unidad}";
                                    })
                                    ->hintColor(fn (Get $get) => ($item = InventoryItem::find($get('inventory_item_id')))
                                        ? ($item->stock_actual <= ($item->stock_minimo ?? 0) ? 'danger' : 'success')
                                        : null)
                                    ->hintIcon(fn (Get $get) => ($item = InventoryItem::find($get('inventory_item_id')))
                                        ? ($item->stock_actual <= ($item->stock_minimo ?? 0) ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                                        : null)
                                    ->afterStateUpdated(fn ($state, Set $set) =>
                                        $set('costo_unitario', InventoryItem::find($state)?->purchase_price ?? 0)),
                                TextInput::make('cantidad_consumida')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('costo_unitario')
                                    ->label('Costo Unitario')
                                    ->numeric()
                                    ->prefix('$')
                                    ->readOnly(),
                            ])
                            ->columns(3)
                            ->itemLabel(fn (array $state): ?string => InventoryItem::find($state['inventory_item_id'] ?? null)?->nombre ?? null)
                            ->addActionLabel('+ Agregar insumo'),
                    ]),

                // ── Paso 3: Notas ────────────────────────────────────
                Wizard\Step::make('Notas')
                    ->schema([
                        Textarea::make('notas')
                            ->label('Notas de Producción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    /**
     * Escala la fórmula de la presentación a la cantidad solicitada y puebla el repeater de materiales.
     * Regla de tres: cantidad_necesaria = (cantidad_formula × cantidad_pedida) / lote_base
     */
    protected static function poblarMateriales(Get $get, Set $set): void
    {
        $presentationId = $get('product_presentation_id');
        $cantidad       = (float) ($get('cantidad_producida') ?? 0);

        if (!$presentationId || $cantidad <= 0) return;

        $presentation = ProductPresentation::with('formulaLines.inventoryItem')->find($presentationId);
        if (!$presentation) return;

        $lote = max((float) $presentation->cantidad_minima_produccion, 0.0001);

        $materials = [];
        foreach ($presentation->formulaLines as $line) {
            if (!$line->inventory_item_id) continue;

            $cantidadEscalada = round(((float) $line->cantidad * $cantidad) / $lote, 6);

            $materials[] = [
                'inventory_item_id' => $line->inventory_item_id,
                'cantidad_consumida' => $cantidadEscalada,
                'costo_unitario'    => (float) ($line->inventoryItem?->purchase_price ?? 0),
            ];
        }

        $set('materials', $materials);
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
                TextColumn::make('productPresentation.nombre')
                    ->label('Presentación')
                    ->placeholder('—'),
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
                        'borrador'   => 'gray',
                        'completado' => 'success',
                        'anulado'    => 'danger',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'borrador'   => 'Borrador',
                        'completado' => 'Completado',
                        'anulado'    => 'Anulado',
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
                    ->modalDescription(function (ProductionOrder $record): string|\Illuminate\Support\HtmlString {
                        $faltantes = [];
                        foreach ($record->materials as $material) {
                            $item = $material->inventoryItem;
                            if (!$item) continue;
                            $necesita = (float) $material->cantidad_consumida;
                            $disponible = (float) $item->stock_actual;
                            if ($disponible < $necesita) {
                                $unidad = $item->measurementUnit?->abreviatura ?? '';
                                $falta = $necesita - $disponible;
                                $fmt = fn (float $n): string => rtrim(rtrim(number_format($n, 4), '0'), '.');
                                $faltantes[] = "<li><strong>{$item->nombre}</strong>: necesita {$fmt($necesita)} {$unidad}, disponible {$fmt($disponible)} {$unidad} — <span style='color:#ef4444'>falta {$fmt($falta)} {$unidad}</span></li>";
                            }
                        }

                        if (!empty($faltantes)) {
                            $lista = implode('', $faltantes);
                            return new \Illuminate\Support\HtmlString(
                                "<p class='mb-2 font-semibold text-red-600'>⚠ Stock insuficiente para los siguientes materiales:</p>"
                                . "<ul class='list-disc pl-4 space-y-1 text-sm mb-3'>{$lista}</ul>"
                                . "<p class='text-sm text-gray-500'>Debes registrar compras para estos insumos antes de completar la orden.</p>"
                            );
                        }

                        return 'Esto generará los movimientos de inventario y el asiento contable automáticamente. Esta acción no se puede deshacer.';
                    })
                    ->visible(fn ($record) => $record->estado === 'borrador')
                    ->action(function (ProductionOrder $record) {
                        // Verificar stock nuevamente antes de ejecutar
                        foreach ($record->materials as $material) {
                            $item = $material->inventoryItem;
                            if (!$item) continue;
                            if ((float) $item->stock_actual < (float) $material->cantidad_consumida) {
                                Notification::make()
                                    ->title('Stock Insuficiente')
                                    ->danger()
                                    ->body("No hay suficiente stock de \"{$item->nombre}\". Registra una compra primero.")
                                    ->send();
                                return;
                            }
                        }

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
            'index'  => Pages\ListProductionOrders::route('/'),
            'create' => Pages\CreateProductionOrder::route('/create'),
            'edit'   => Pages\EditProductionOrder::route('/{record}/edit'),
        ];
    }
}
