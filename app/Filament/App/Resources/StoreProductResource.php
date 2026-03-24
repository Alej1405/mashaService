<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreProductResource\Pages;
use App\Models\ProductDesign;
use App\Models\ProductPresentation;
use App\Models\StoreCategory;
use App\Models\StoreProduct;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class StoreProductResource extends Resource
{
    protected static ?string $model = StoreProduct::class;

    protected static ?string $tenantRelationshipName = 'storeProducts';

    protected static ?string $navigationIcon   = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel  = 'Productos';
    protected static ?string $navigationGroup  = 'E-Commerce';
    protected static ?string $modelLabel       = 'Producto de Tienda';
    protected static ?string $pluralModelLabel = 'Productos de Tienda';
    protected static ?int    $navigationSort   = 1;

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
            Tabs::make()
                ->tabs([
                    Tab::make('Producto')
                        ->icon('heroicon-o-cube')
                        ->schema([

                            // ── Cargar desde Diseño de Producto ──────────────
                            Select::make('product_design_id')
                                ->label('📐 Diseño de Producto')
                                ->options(fn () => ProductDesign::where('activo', true)
                                    ->get()
                                    ->mapWithKeys(fn ($d) => [$d->id => $d->nombre . ($d->categoria ? '  —  ' . $d->categoria : '')]))
                                ->searchable()
                                ->nullable()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?int $state) {
                                    if (!$state) return;
                                    $design = ProductDesign::with('presentations')->find($state);
                                    if (!$design) return;

                                    // Poblar campos básicos del diseño
                                    $set('nombre', $design->nombre);
                                    if ($design->propuesta_valor) {
                                        $set('descripcion', $design->propuesta_valor);
                                    }

                                    // Categoría desde el diseño
                                    if ($design->store_category_id) {
                                        $set('store_category_id', $design->store_category_id);
                                    }

                                    // Si tiene una sola presentación, cargarla directo
                                    $presentations = $design->presentations->where('activa', true);
                                    if ($presentations->count() === 1) {
                                        $pres = $presentations->first();
                                        $set('product_presentation_id', $pres->id);
                                        if ($pres->pvp_estimado > 0) {
                                            $set('precio_venta', $pres->pvp_estimado);
                                        }
                                        $set('slug', Str::slug($design->nombre . '-' . $pres->nombre));
                                    } else {
                                        $set('product_presentation_id', null);
                                        $set('slug', Str::slug($design->nombre));
                                    }
                                })
                                ->helperText('Selecciona el diseño de producto. Se autocompletarán nombre, descripción y precio.')
                                ->columnSpanFull(),

                            Select::make('product_presentation_id')
                                ->label('Presentación')
                                ->options(function (Get $get) {
                                    $designId = $get('product_design_id');
                                    if (!$designId) return [];
                                    return ProductPresentation::where('product_design_id', $designId)
                                        ->where('activa', true)
                                        ->get()
                                        ->mapWithKeys(fn ($p) => [
                                            $p->id => $p->nombre . ($p->pvp_estimado > 0 ? '  —  PVP $ ' . number_format($p->pvp_estimado, 2) : ''),
                                        ]);
                                })
                                ->nullable()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?int $state) {
                                    if (!$state) return;
                                    $pres = ProductPresentation::find($state);
                                    if (!$pres) return;
                                    if ($pres->pvp_estimado > 0) {
                                        $set('precio_venta', $pres->pvp_estimado);
                                    }
                                    if ($pres->precio_distribuidor > 0) {
                                        $set('precio_distribuidor', $pres->precio_distribuidor);
                                    }
                                    $design = ProductDesign::find($get('product_design_id'));
                                    $set('slug', Str::slug(($design?->nombre ?? '') . '-' . $pres->nombre));
                                })
                                ->helperText('Si el diseño tiene varias presentaciones, selecciona cuál publicar.')
                                ->visible(fn (Get $get) => (bool) $get('product_design_id'))
                                ->columnSpan(2),

                            // ── Campos editables (pre-cargados o manuales) ───
                            Select::make('store_category_id')
                                ->label('Categoría')
                                ->options(fn () => StoreCategory::where('publicado', true)
                                    ->pluck('nombre', 'id'))
                                ->searchable()
                                ->nullable()
                                ->createOptionModalHeading('Nueva Categoría')
                                ->createOptionForm([
                                    TextInput::make('nombre')
                                        ->label('Nombre')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, ?string $state) =>
                                            $set('slug', \Illuminate\Support\Str::slug($state ?? ''))),
                                    TextInput::make('slug')
                                        ->label('Slug')
                                        ->required(),
                                    \Filament\Forms\Components\Toggle::make('publicado')
                                        ->label('Publicada')
                                        ->default(true),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    return StoreCategory::create([
                                        ...$data,
                                        'empresa_id' => filament()->getTenant()->id,
                                    ])->getKey();
                                })
                                ->columnSpan(1),

                            TextInput::make('nombre')
                                ->label('Nombre en Tienda')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) =>
                                    $set('slug', Str::slug($state ?? '')))
                                ->columnSpan(2),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),
                            TextInput::make('precio_venta')
                                ->label('PVP (precio público)')
                                ->numeric()
                                ->required()
                                ->prefix('$')
                                ->helperText('Cargado desde la presentación. Puedes ajustarlo.')
                                ->columnSpan(1),

                            TextInput::make('precio_distribuidor')
                                ->label('Precio Distribuidor (10+ unidades)')
                                ->numeric()
                                ->default(0)
                                ->prefix('$')
                                ->helperText('Se aplica automáticamente cuando el cliente compra 10 o más unidades.')
                                ->columnSpan(1),

                            RichEditor::make('descripcion')
                                ->label('Descripción')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                                ->columnSpanFull(),
                        ])->columns(3),

                    Tab::make('Tienda')
                        ->icon('heroicon-o-megaphone')
                        ->schema([
                            Toggle::make('publicado')
                                ->label('Publicado')
                                ->helperText('Solo los productos publicados son visibles en la tienda.')
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
                                ->disk('public')
                                ->directory('store/products/gallery')
                                ->columnSpanFull(),
                        ])->columns(3),
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
                TextColumn::make('productDesign.inventoryItem.stock_actual')
                    ->label('Stock')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
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
