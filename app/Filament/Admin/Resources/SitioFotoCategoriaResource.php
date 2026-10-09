<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioFotoCategoriaResource\Pages;
use App\Models\SitioFotoCategoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Las categorías de fotografía. Cada una es una tarjeta de «Qué
 * fotografiamos» en /fotografia y agrupa sus álbumes en el portafolio.
 */
class SitioFotoCategoriaResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioFotoCategoria::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-tag';
    protected static ?string $navigationLabel        = 'Fotografía · Categorías';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 3;
    protected static ?string $modelLabel             = 'Categoría de fotos';
    protected static ?string $pluralModelLabel       = 'Categorías de fotos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('La tarjeta en «Qué fotografiamos»')->columns(2)->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')->required()->maxLength(80)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                Forms\Components\TextInput::make('slug')
                    ->label('En la dirección')->helperText('mashaec.net/fotografia?categoria=…')
                    ->required()->maxLength(80),
                Forms\Components\Textarea::make('descripcion')->label('Qué se fotografía')->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('para')->label('Para qué sirve')->helperText('La línea chica de abajo. Ej: «Para tiendas en línea».')->maxLength(160)->columnSpanFull(),
                Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Categoría')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('para')->label('Para qué sirve')->color('gray')->limit(50),
                Tables\Columns\TextColumn::make('albumes_count')->label('Álbumes')->counts('albumes'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioFotoCategorias::route('/'),
            'create' => Pages\CreateSitioFotoCategoria::route('/create'),
            'edit'   => Pages\EditSitioFotoCategoria::route('/{record}/edit'),
        ];
    }
}
