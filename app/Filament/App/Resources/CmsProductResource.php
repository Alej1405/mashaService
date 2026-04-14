<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CmsProductResource\Pages;
use App\Models\CmsProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsProductResource extends Resource
{
    protected static ?string $model = CmsProduct::class;

    protected static ?string $tenantRelationshipName = 'cmsProducts';

    protected static ?string $navigationIcon   = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel  = 'Productos';
    protected static ?string $navigationGroup  = 'CMS';
    protected static ?int    $navigationSort   = 4;
    protected static ?string $modelLabel       = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre del producto')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(3)
                    ->maxLength(600)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('precio')
                    ->label('Precio (sin IVA)')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('Ej: 12.50'),

                Forms\Components\TextInput::make('unidad_precio')
                    ->label('Unidad de precio')
                    ->placeholder('por kg, por unidad, por hora...')
                    ->helperText('Ej: "por kg", "por unidad", "tarifa fija"'),

                Forms\Components\TextInput::make('categoria')
                    ->label('Categoría')
                    ->placeholder('Ej: Limpieza, Alimentos, Servicios...')
                    ->maxLength(80),

                Forms\Components\TextInput::make('icono')
                    ->label('Ícono (emoji o heroicon)')
                    ->placeholder('📦  ó  heroicon-o-cube')
                    ->maxLength(60),

                Forms\Components\Repeater::make('caracteristicas')
                    ->label('Características del producto')
                    ->schema([
                        Forms\Components\TextInput::make('texto')
                            ->label('Característica')
                            ->required()
                            ->maxLength(200)
                            ->placeholder('Ej: Disponible en todo el país'),
                    ])
                    ->addActionLabel('Agregar característica')
                    ->defaultItems(0)
                    ->collapsible()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('imagen')
                    ->label('Imagen del producto')
                    ->image()
                    ->disk('public')
                    ->directory('cms/products')
                    ->imagePreviewHeight('80')
                    ->maxSize(2048)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('activo')
                    ->label('Visible en el sitio')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->width(48)->height(48)->rounded(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? '$ ' . number_format((float) $state, 2) . ($record->unidad_precio ? ' ' . $record->unidad_precio : '')
                        : '—'),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Visible')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsProducts::route('/'),
            'create' => Pages\CreateCmsProduct::route('/create'),
            'edit'   => Pages\EditCmsProduct::route('/{record}/edit'),
        ];
    }
}
