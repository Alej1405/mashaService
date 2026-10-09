<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioPaginaResource\Pages;
use App\Models\SitioPagina;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SitioPaginaResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioPagina::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-document-text';
    protected static ?string $navigationLabel        = 'Páginas';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 1;
    protected static ?string $modelLabel             = 'Página';
    protected static ?string $pluralModelLabel       = 'Páginas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identidad de la página')
                ->description('El slug es la URL de la sección en el sitio: /desarrollo, /fotografia…')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('slug')
                        ->label('Sección')
                        ->options(fn (): array => array_combine(config('sitio.paginas'), config('sitio.paginas')))
                        ->live()
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Orden en el inicio')
                        ->helperText('Define el orden de las fuerzas en la portada.')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('titulo')
                        ->label('Título')->required()->maxLength(150)->columnSpanFull(),
                    Forms\Components\TextInput::make('subtitulo')
                        ->label('Subtítulo')->maxLength(200)->columnSpanFull(),
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción')->rows(3)->maxLength(600)->columnSpanFull(),
                    Forms\Components\FileUpload::make('imagen')
                        ->label('Imagen')
                        ->helperText('La que se ve en el sitio. Si está vacío, la sección sale sin imagen.')
                        ->image()->disk('public')->directory('sitio/paginas')
                        // 80 px no alcanza para reconocer una foto: se ve una
                        // mancha y hay que abrirla para saber cuál es.
                        ->imagePreviewHeight('160')
                        ->openable()
                        ->downloadable(),
                    Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
                ]),

            Forms\Components\Section::make('Bloques')
                ->description('Cada bloque es una tarjeta o un apartado dentro de la página.')
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('bloques')
                        ->label('Bloques de contenido')
                        ->schema([
                            // El tipo decide dónde cae el bloque en la página.
                            // Sin esto, el sitio no sabía distinguir una foto de
                            // la galería de una tarjeta de servicio, y todo
                            // terminaba escrito a mano en el código.
                            // La sección dice qué parte de la web reemplaza este
                            // bloque. Sin sección, el bloque se pinta al final de
                            // la página tal cual: nada de lo que se agrega se pierde.
                            Forms\Components\Select::make('seccion')
                                ->label('Dónde va')
                                ->options(fn (Forms\Get $get): array => config('sitio.secciones.' . $get('../../slug'), []))
                                ->placeholder('Al final de la página')
                                ->helperText('Si eliges una sección, sus bloques reemplazan el contenido por defecto de esa parte.')
                                ->native(false),
                            Forms\Components\Select::make('tipo')
                                ->label('Qué es este bloque')
                                ->options([
                                    'encabezado' => 'Encabezado de la sección (título, entrada, nota y botón)',
                                    'tarjeta'    => 'Tarjeta (título, texto y apoyo)',
                                    'grupo'      => 'Título de grupo (agrupa los bloques que siguen)',
                                    'nota'       => 'Nota destacada (fondo oscuro)',
                                    'enlace'     => 'Enlace a otra parte',
                                    'texto'      => 'Solo texto',
                                    'galeria'    => 'Foto de la galería',
                                ])
                                ->default('tarjeta')
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('titulo')
                                ->label('Título')->maxLength(150),
                            Forms\Components\Textarea::make('texto')
                                ->label('Texto')->rows(3)->columnSpanFull(),
                            Forms\Components\TextInput::make('apoyo')
                                ->label('Línea de apoyo')
                                ->helperText('La frase chica de abajo. Ej: «Para tiendas en línea».')
                                ->maxLength(160),
                            Forms\Components\TextInput::make('accion')
                                ->label('Texto del botón')
                                ->maxLength(80)
                                ->visible(fn (Forms\Get $get): bool => in_array($get('tipo'), ['encabezado', 'tarjeta', 'nota', 'enlace'], true)),
                            Forms\Components\TextInput::make('enlace')
                                ->label('Enlace')
                                ->helperText('Una ruta del sitio (/erp, /#contacto) o una dirección completa.')
                                ->maxLength(300)
                                ->visible(fn (Forms\Get $get): bool => $get('tipo') !== 'galeria'),
                            Forms\Components\TagsInput::make('lista')
                                ->label('Lista de puntos')
                                ->helperText('Escribe un punto y presiona Enter.')
                                ->reorderable()
                                ->columnSpanFull()
                                ->visible(fn (Forms\Get $get): bool => in_array($get('tipo'), ['tarjeta', 'grupo', 'texto'], true)),
                            Forms\Components\TextInput::make('icono')
                                ->label('Ícono (emoji o heroicon)')->maxLength(60)
                                ->visible(fn (Forms\Get $get): bool => $get('tipo') !== 'galeria'),
                            Forms\Components\FileUpload::make('imagen')
                                ->label('Imagen')
                                ->helperText('Obligatoria en las fotos de la galería.')
                                ->image()->disk('public')->directory('sitio/bloques')
                                ->imagePreviewHeight('160')
                                ->openable()
                                ->downloadable()
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state): ?string => trim(($state['seccion'] ?? 'al final') . ' · ' . ($state['tipo'] ?? '') . ' · ' . ($state['titulo'] ?? 'sin título'), ' ·'))
                        ->reorderableWithButtons()
                        ->addActionLabel('Agregar bloque')
                        ->defaultItems(0)
                        ->collapsible()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Cuerpo largo')
                ->description('Texto en markdown. Se usa sobre todo en /proceso.')
                ->collapsed()
                ->schema([
                    Forms\Components\MarkdownEditor::make('cuerpo')->label('Cuerpo')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                // La miniatura primero: de un vistazo se ve qué secciones
                // tienen imagen y cuáles no, sin entrar a cada una.
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->disk('public')
                    ->height(40)
                    ->defaultImageUrl(null)
                    ->tooltip(fn ($record) => $record->imagen ? 'Con imagen' : 'Sin imagen'),
                Tables\Columns\TextColumn::make('slug')->label('Sección')->badge()->searchable(),
                Tables\Columns\TextColumn::make('titulo')->label('Título')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('subtitulo')->label('Subtítulo')->limit(50)->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioPaginas::route('/'),
            'create' => Pages\CreateSitioPagina::route('/create'),
            'edit'   => Pages\EditSitioPagina::route('/{record}/edit'),
        ];
    }
}
