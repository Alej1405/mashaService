<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioArticuloResource\Pages;
use App\Filament\Cms\Resources\CmsPostResource;
use App\Models\CmsPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Articulos del Laboratorio. Son los mismos posts del CMS — no hay tabla
 * aparte — con los campos que el indice necesita: resumen, serie y minutos
 * de lectura.
 */
class SitioArticuloResource extends CmsPostResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-beaker';
    protected static ?string $navigationLabel        = 'Artículos';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 15;
    protected static ?string $modelLabel             = 'Artículo';
    protected static ?string $pluralModelLabel       = 'Artículos del Laboratorio';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('El artículo')->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')->required()->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('slug')
                    ->label('URL amigable (slug)')->required()->maxLength(200)->columnSpanFull(),
                Forms\Components\Textarea::make('resumen')
                    ->label('Resumen')
                    ->helperText('Lo que se lee en el índice del Laboratorio. Si lo dejas vacío se corta del contenido.')
                    ->rows(2)->maxLength(300)->columnSpanFull(),
                Forms\Components\RichEditor::make('contenido')
                    ->label('Contenido')->required()
                    ->toolbarButtons(['bold','italic','underline','bulletList','orderedList','h2','h3','paragraph','link','blockquote','codeBlock','undo','redo'])
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('imagen')
                    ->label('Imagen principal')->image()->disk('public')->directory('cms/posts')
                    ->imagePreviewHeight('120')
                    ->helperText('JPG o PNG. Recomendado: 1200×630 px.')->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Publicación')->columns(3)->schema([
                Forms\Components\TextInput::make('serie')
                    ->label('Serie')
                    ->helperText('Agrupa artículos de un mismo tema.')
                    ->maxLength(80),
                Forms\Components\TextInput::make('minutos_lectura')
                    ->label('Minutos de lectura')->numeric()->minValue(1)->maxValue(120),
                Forms\Components\DateTimePicker::make('publicado_en')
                    ->label('Fecha de publicación')
                    ->helperText('Sin fecha no sale en la API.')
                    ->native(false),
                Forms\Components\Toggle::make('destacado')->label('Destacado'),
                Forms\Components\Toggle::make('activo')->label('Publicado')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('publicado_en', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->height(40)->width(64),
                Tables\Columns\TextColumn::make('titulo')->label('Título')->searchable()
                    ->description(fn (CmsPost $r) => $r->slug)->weight('semibold'),
                Tables\Columns\TextColumn::make('serie')->label('Serie')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('minutos_lectura')->label('Min')->alignCenter()->placeholder('—'),
                Tables\Columns\TextColumn::make('publicado_en')->label('Publicado')
                    ->since()->placeholder('Sin fecha')->sortable()->color('gray'),
                Tables\Columns\IconColumn::make('destacado')->label('Destacado')->boolean(),
                Tables\Columns\ToggleColumn::make('activo')->label('Publicado'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioArticulo::route('/'),
            'create' => Pages\CreateSitioArticulo::route('/create'),
            'edit'   => Pages\EditSitioArticulo::route('/{record}/edit'),
        ];
    }
}
