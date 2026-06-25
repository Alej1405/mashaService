<?php

namespace App\Filament\Ecommerce\Resources;

use App\Filament\Ecommerce\Resources\StoreProductResource\Pages;
use App\Models\ProductDesign;
use App\Models\ProductPresentation;
use App\Models\StoreCategory;
use App\Models\StoreProduct;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
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
    protected static ?string $navigationIcon         = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel        = 'Productos';
    protected static ?string $navigationGroup        = 'Catálogo';
    protected static ?string $modelLabel             = 'Producto de Tienda';
    protected static ?string $pluralModelLabel       = 'Productos de Tienda';
    protected static ?int    $navigationSort         = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make()->tabs([
                Tab::make('Información')->schema([
                    TextInput::make('nombre')->label('Nombre')->required()->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
                        ->columnSpan(2),
                    TextInput::make('slug')->label('Slug')->required()->maxLength(200)->columnSpan(1),
                    Select::make('store_category_id')->label('Categoría')
                        ->options(fn () => StoreCategory::pluck('nombre', 'id'))
                        ->nullable()->searchable()->native(false)->columnSpan(1),
                    TextInput::make('sku')->label('SKU')->maxLength(100)->columnSpan(1),
                    Toggle::make('publicado')->label('Publicado')->default(true)->columnSpan(1),
                    RichEditor::make('descripcion')->label('Descripción')
                        ->toolbarButtons(['bold','italic','bulletList','orderedList','link','undo','redo'])
                        ->columnSpanFull(),
                    FileUpload::make('imagen_principal')->label('Imagen principal')
                        ->image()->disk('public')->directory('store/products')
                        ->imagePreviewHeight('120')->maxSize(3072)->columnSpanFull(),
                ])->columns(3),

                Tab::make('Landing / Vitrina')->schema([
                    TextInput::make('unidad_precio')
                        ->label('Unidad de precio')
                        ->placeholder('por kg, por unidad, por caja...')
                        ->maxLength(80)
                        ->helperText('Se muestra junto al precio en la landing y el catálogo.')
                        ->columnSpanFull(),
                    Repeater::make('caracteristicas')
                        ->label('Características del producto')
                        ->schema([
                            TextInput::make('texto')
                                ->label('Característica')
                                ->required()
                                ->maxLength(200)
                                ->placeholder('Envío en 24 h, Material premium...'),
                        ])
                        ->addActionLabel('Agregar característica')
                        ->defaultItems(0)
                        ->collapsible()
                        ->helperText('Bullets que aparecen en la landing page del producto.')
                        ->columnSpanFull(),
                    TextInput::make('meta_titulo')
                        ->label('Título SEO / OG')
                        ->maxLength(200)
                        ->placeholder('Título para redes sociales y buscadores')
                        ->helperText('Si se deja vacío se usa el nombre del producto.')
                        ->columnSpanFull(),
                    Textarea::make('meta_descripcion')
                        ->label('Descripción SEO / OG')
                        ->rows(3)
                        ->maxLength(300)
                        ->placeholder('Descripción corta para compartir en redes sociales...')
                        ->helperText('Aparece como descripción en WhatsApp, Instagram, etc. Máximo 300 caracteres.')
                        ->columnSpanFull(),
                ]),

                Tab::make('Precios')->schema([
                    Select::make('product_design_id')->label('Diseño de producto (ERP)')
                        ->options(fn () => ProductDesign::pluck('nombre', 'id'))
                        ->nullable()->searchable()->native(false)->live()
                        ->afterStateUpdated(fn (Set $set) => $set('product_presentation_id', null))
                        ->columnSpanFull(),
                    Select::make('product_presentation_id')->label('Presentación')
                        ->options(fn (Get $get) => $get('product_design_id')
                            ? ProductPresentation::where('product_design_id', $get('product_design_id'))->pluck('nombre', 'id')
                            : [])
                        ->nullable()->searchable()->native(false)->columnSpanFull(),
                    TextInput::make('precio_venta')->label('Precio base')->numeric()->prefix('$')->required()->columnSpan(1),
                    TextInput::make('precio_distribuidor')->label('Precio distribuidor')->numeric()->prefix('$')->nullable()->columnSpan(1),
                    TextInput::make('cantidad_minima_distribuidor')->label('Cant. mín. distribuidor')->numeric()->default(1)->columnSpan(1),
                    TextInput::make('stock')->label('Stock disponible')->numeric()->default(0)->columnSpan(1),
                    TextInput::make('stock_minimo')->label('Stock mínimo')->numeric()->default(0)->columnSpan(1),
                    Toggle::make('gestionar_stock')->label('Gestionar stock')->default(true)->columnSpan(1),
                ])->columns(3),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('imagen_principal')->label('')->height(40)->width(48),
                TextColumn::make('nombre')->label('Producto')->searchable()->sortable()->weight('semibold')
                    ->description(fn ($record) => $record->sku ? 'SKU: ' . $record->sku : null),
                TextColumn::make('storeCategory.nombre')->label('Categoría')->badge()->color('primary'),
                TextColumn::make('precio_venta')->label('Precio')->money('USD')->sortable(),
                TextColumn::make('stock')->label('Stock')->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state < 5 ? 'warning' : 'success')),
                IconColumn::make('publicado')->label('Publicado')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([TernaryFilter::make('publicado')->label('Publicado')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([\Filament\Tables\Actions\BulkActionGroup::make([\Filament\Tables\Actions\DeleteBulkAction::make()])]);
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
