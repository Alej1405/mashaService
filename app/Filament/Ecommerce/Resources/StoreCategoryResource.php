<?php

namespace App\Filament\Ecommerce\Resources;

use App\Filament\Ecommerce\Resources\StoreCategoryResource\Pages;
use App\Models\StoreCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class StoreCategoryResource extends Resource
{
    protected static ?string $model = StoreCategory::class;

    protected static ?string $tenantRelationshipName = 'storeCategories';
    protected static ?string $navigationIcon         = 'heroicon-o-tag';
    protected static ?string $navigationLabel        = 'Categorías';
    protected static ?string $navigationGroup        = 'Catálogo';
    protected static ?string $modelLabel             = 'Categoría';
    protected static ?string $pluralModelLabel       = 'Categorías';
    protected static ?int    $navigationSort         = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Datos de la categoría')
                ->description('Información base y jerarquía del catálogo.')
                ->columns(3)
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
                        ->columnSpan(2),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(1),
                    Select::make('parent_id')
                        ->label('Categoría Padre')
                        ->options(fn () => StoreCategory::whereNull('parent_id')
                        ->pluck('nombre', 'id'))
                        ->nullable()
                        ->searchable()
                        ->native(false)
                        ->columnSpan(1),
                    TextInput::make('orden')
                        ->label('Orden')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(1),
                    Toggle::make('publicado')
                        ->label('Publicada')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),
                    Toggle::make('destacada')
                        ->label('Destacada en catálogo')
                        ->default(false)
                        ->inline(false)
                        ->columnSpan(1),
                    Textarea::make('descripcion')
                        ->label('Descripción corta')
                        ->rows(2)
                        ->columnSpanFull(),
                    FileUpload::make('imagen')
                        ->label("Imagen (miniatura)")
                        ->image()
                        ->disk('public')
                        ->directory('store/categories')
                        ->imagePreviewHeight('80')
                        ->maxSize(2048)
                        ->columnSpanFull(),
                ]),

            Section::make('Landing de la categoría')
                ->description('Lo que el frontend usa para generar la página de la categoría.')
                ->collapsible()
                ->columns(2)
                ->schema([
                    FileUpload::make('banner')
                        ->label('Banner de cabecera')
                        ->image()
                        ->disk('public')
                        ->directory('store/categories/banners')
                        ->imagePreviewHeight('100')
                        ->maxSize(4096)
                        ->columnSpanFull(),
                    RichEditor::make('contenido')
                        ->label('Contenido de la landing')
                        ->toolbarButtons(['bold', 'italic', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                        ->columnSpanFull(),
                    TextInput::make('meta_titulo')
                        ->label('Meta título (SEO)')
                        ->maxLength(160)->columnSpan(1),
                    Textarea::make('meta_descripcion')
                        ->label('Meta descripción (SEO)')
                        ->rows(2)
                        ->maxLength(255)
                        ->columnSpan(1),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('orden')->defaultSort('orden')
            ->columns([
                TextColumn::make('nombre')->label('Categoría')->searchable()->weight('semibold'),
                TextColumn::make('parent.nombre')->label('Padre')->badge()->color('gray'),
                TextColumn::make('slug')->label('Slug')->color('gray'),
                IconColumn::make('destacada')->label('Destacada')->boolean(),
                IconColumn::make('publicado')->label('Publicada')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([\Filament\Tables\Actions\BulkActionGroup::make([\Filament\Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStoreCategories::route('/'),
            'create' => Pages\CreateStoreCategory::route('/create'),
            'edit'   => Pages\EditStoreCategory::route('/{record}/edit'),
        ];
    }
}
