<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreCategoryResource\Pages;
use App\Models\StoreCategory;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
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

    protected static ?string $navigationIcon   = 'heroicon-o-tag';
    protected static ?string $navigationLabel  = 'Categorías';
    protected static ?string $navigationGroup  = 'E-Commerce';
    protected static ?string $modelLabel       = 'Categoría';
    protected static ?string $pluralModelLabel = 'Categorías';
    protected static ?int    $navigationSort   = 2;

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
            TextInput::make('nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) =>
                    $set('slug', Str::slug($state ?? '')))
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
                ->createOptionModalHeading('Nueva Categoría Padre')
                ->createOptionForm([
                    TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) =>
                            $set('slug', Str::slug($state ?? ''))),
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
            TextInput::make('orden')
                ->label('Orden')
                ->numeric()
                ->default(0)
                ->columnSpan(1),
            Toggle::make('publicado')
                ->label('Publicada')
                ->default(true)
                ->columnSpan(1),
            Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(3)
                ->columnSpanFull(),
            FileUpload::make('imagen')
                ->label('Imagen')
                ->image()
                ->disk('public')
                ->directory('store/categories')
                ->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('parent.nombre')
                    ->label('Padre')
                    ->placeholder('—'),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Productos')
                    ->badge()
                    ->color('info'),
                IconColumn::make('publicado')
                    ->label('Publicada')
                    ->boolean(),
                TextColumn::make('orden')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->defaultSort('orden')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
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
