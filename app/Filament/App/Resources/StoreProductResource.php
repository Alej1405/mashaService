<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreProductResource\Pages;
use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\StoreProduct;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Módulo Producto — opera sobre la tabla ÚNICA de productos (store_products).
 *
 * Es la misma tabla que usa la Tienda: un detalle agregado aquí se ve en la
 * Tienda y viceversa (una sola verdad, sin duplicar). Costos, presentaciones e
 * inventario quedan FUERA por decisión de arquitectura (ver memoria).
 *
 * Publicación: aquí (ERP) NO se publica por defecto — el usuario decide con el
 * toggle "Publicar en la tienda". Desde el panel de Tienda sí se publica directo.
 */
class StoreProductResource extends Resource
{
    protected static ?string $model = StoreProduct::class;

    protected static ?string $tenantRelationshipName = 'storeProducts';

    protected static ?string $navigationIcon   = 'heroicon-o-cube';
    protected static ?string $navigationLabel  = 'Productos';
    protected static ?string $navigationGroup  = 'Producto';
    protected static ?string $modelLabel       = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';
    protected static ?int    $navigationSort   = 1;

    public static function canAccess(): bool
    {
        // En modo AISLAR PRODUCTO es el único visible (se auto-permite).
        if (\App\Helpers\PlanHelper::aislarProducto()) {
            return true;
        }

        return \App\Helpers\PlanHelper::hasModule('tienda');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make()
                ->tabs([
                    Tab::make('Producto')
                        ->icon('heroicon-o-cube')
                        ->schema([
                            TextInput::make('nombre')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                                    if ($operation === 'create' && filled($state)) {
                                        $set('slug', Str::slug($state));
                                    }
                                })
                                ->columnSpan(2),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Identificador para la URL. Se genera del nombre; puedes ajustarlo.')
                                ->columnSpan(1),

                            Select::make('store_category_id')
                                ->label('Categoría')
                                ->relationship('storeCategory', 'nombre')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->columnSpan(1),

                            TextInput::make('precio_venta')
                                ->label('PVP (precio público)')
                                ->numeric()
                                ->required()
                                ->prefix('$')
                                ->columnSpan(1),

                            TextInput::make('sku')
                                ->label('SKU')
                                ->maxLength(255)
                                ->nullable()
                                ->columnSpan(1),

                            RichEditor::make('descripcion')
                                ->label('Descripción')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                                ->columnSpanFull(),
                        ])->columns(3),

                    Tab::make('Publicación')
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            Toggle::make('publicado')
                                ->label('Publicar en la tienda')
                                ->helperText('No todos los productos del ERP se publican. Actívalo solo si este debe aparecer en el catálogo web.')
                                ->default(false),
                            Toggle::make('destacado')
                                ->label('Destacado')
                                ->helperText('Aparece en la sección de productos destacados del Home.'),
                            TextInput::make('orden')
                                ->label('Orden')
                                ->numeric()
                                ->default(0)
                                ->columnSpan(1),
                            FileUpload::make('imagen_principal')
                                ->label('Imagen Principal')
                                ->image()
                                ->disk('public')
                                ->directory('store/products')
                                ->columnSpanFull(),
                            FileUpload::make('galeria')
                                ->label('Galería')
                                ->image()
                                ->multiple()
                                ->maxFiles(5)
                                ->reorderable()
                                ->appendFiles()
                                ->maxSize(3072)
                                ->disk('public')
                                ->directory('store/products/gallery')
                                ->helperText('Hasta 5 imágenes.')
                                ->columnSpanFull(),
                        ])->columns(3),

                    Tab::make('Insumos')
                        ->icon('heroicon-o-beaker')
                        ->schema([
                            Repeater::make('materiales')
                                ->relationship()
                                ->label('Insumos y materia prima que componen el producto')
                                ->schema([
                                    Select::make('inventory_item_id')
                                        ->label('Insumo / materia prima')
                                        ->options(fn () => InventoryItem::query()
                                            ->whereIn('type', ['insumo', 'materia_prima'])
                                            ->where('activo', true)
                                            ->orderBy('nombre')
                                            ->get()
                                            ->mapWithKeys(fn ($i) => [$i->id => trim(($i->codigo ? $i->codigo . ' · ' : '') . $i->nombre)]))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            // Ayuda: al elegir el insumo, sugiere su unidad de medida.
                                            $item = $state ? InventoryItem::find($state) : null;
                                            if ($item?->measurement_unit_id) {
                                                $set('measurement_unit_id', $item->measurement_unit_id);
                                            }
                                        })
                                        ->helperText('Solo aparecen ítems de tipo insumo o materia prima.')
                                        ->columnSpan(3),

                                    // Ayuda: detalle del insumo elegido (proveedor, stock, costo).
                                    Placeholder::make('info_insumo')
                                        ->label('')
                                        ->content(function (Get $get): HtmlString {
                                            $id = $get('inventory_item_id');
                                            if (! $id) {
                                                return new HtmlString('<span style="color:#9ca3af">Elige un insumo para ver su proveedor, stock y costo.</span>');
                                            }
                                            $i = InventoryItem::with(['supplier', 'measurementUnit'])->find($id);
                                            if (! $i) {
                                                return new HtmlString('—');
                                            }
                                            $prov  = $i->supplier?->nombre ?? 'sin proveedor';
                                            $um    = $i->measurementUnit?->abreviatura ?? '—';
                                            $stock = rtrim(rtrim(number_format((float) $i->stock_actual, 4, '.', ''), '0'), '.');
                                            $bajo  = $i->stock_actual <= $i->stock_minimo
                                                ? ' <span style="color:#dc2626;font-weight:600">(stock bajo)</span>' : '';
                                            return new HtmlString(
                                                "🏭 Proveedor: <b>{$prov}</b> &nbsp;·&nbsp; 📦 Stock: <b>{$stock} {$um}</b>{$bajo} &nbsp;·&nbsp; 💲 Costo: <b>$" . number_format((float) $i->purchase_price, 2) . "</b>"
                                            );
                                        })
                                        ->columnSpanFull(),

                                    TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.0001)
                                        ->helperText('Cuánto de este insumo lleva una unidad del producto.')
                                        ->columnSpan(1),
                                    Select::make('measurement_unit_id')
                                        ->label('Unidad')
                                        ->options(fn () => MeasurementUnit::query()
                                            ->where('activo', true)
                                            ->orderBy('nombre')
                                            ->pluck('abreviatura', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->helperText('Se sugiere la del insumo; puedes cambiarla.')
                                        ->columnSpan(1),
                                    TextInput::make('notas')
                                        ->label('Notas')
                                        ->maxLength(255)
                                        ->placeholder('Opcional: color, calibre, observación…')
                                        ->columnSpan(1),
                                ])
                                ->columns(3)
                                ->itemLabel(fn (array $state): ?string => ($state['inventory_item_id'] ?? null)
                                    ? optional(InventoryItem::find($state['inventory_item_id']))->nombre
                                    : 'Nuevo insumo')
                                ->addActionLabel('Agregar insumo')
                                ->defaultItems(0)
                                ->reorderable(false)
                                ->collapsible()
                                ->cloneable()
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('imagen_principal')
                    ->label('')
                    ->disk('public')
                    ->width(48)
                    ->height(48)
                    ->circular(),
                TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('storeCategory.nombre')
                    ->label('Categoría')
                    ->badge()
                    ->placeholder('Sin categoría'),
                TextColumn::make('precio_venta')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('publicado')
                    ->label('Publicado')
                    ->boolean(),
                IconColumn::make('destacado')
                    ->label('Destacado')
                    ->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([
                TernaryFilter::make('publicado'),
                TernaryFilter::make('destacado'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStoreProducts::route('/'),
            'create' => Pages\CreateStoreProduct::route('/create'),
            'edit'   => Pages\EditStoreProduct::route('/{record}/edit'),
        ];
    }
}
