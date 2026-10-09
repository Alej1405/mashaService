<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioPaginaResource\Pages;
use App\Models\SitioPagina;
use App\Support\SitioPropio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Cada página del sitio se edita aquí, completa, en un solo formulario:
 * encabezado con su única imagen, secciones, planes y cómo se comparte.
 *
 * A la derecha va la miniatura en vivo: marca la parte de la página que se
 * está editando y dice si esa parte sale del panel o del contenido por
 * defecto. Lo que este formulario pide, la web lo pinta.
 */
class SitioPaginaResource extends Resource
{
        use DelSitioPropio {
        getEloquentQuery as consultaDelSitio;
    }


    protected static ?string $model = SitioPagina::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-document-text';
    protected static ?string $navigationLabel        = 'Páginas';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 1;
    protected static ?string $modelLabel             = 'Página';
    protected static ?string $pluralModelLabel       = 'Páginas';

    /** Páginas de la web que muestran planes. */
    private const CON_PLANES = ['desarrollo', 'fotografia', 'erp', 'erp-modulos'];

    /** Dónde se ve la imagen de la página, según cuál sea. */
    private const IMAGEN_EN = [
        'inicio'     => 'Se ve a la derecha del titular y al compartir el enlace.',
        'fotografia' => 'Es la foto grande de la galería y la que sale al compartir.',
    ];

    public static function getEloquentQuery(): Builder
    {
        // Solo las páginas que la web tiene; una que no existe en la web no se edita.
                return static::consultaDelSitio()->whereIn('sitio_paginas.slug', config('sitio.paginas'));
    }

    public static function form(Form $form): Form
    {
        return $form->columns(['default' => 1, 'xl' => 5])->schema([
            Forms\Components\Group::make()->columnSpan(['default' => 1, 'xl' => 3])->schema([

                Forms\Components\Section::make('Página')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('slug')
                            ->label('Página de la web')
                            ->options(fn (): array => collect(config('sitio.paginas'))
                                ->mapWithKeys(fn (string $p) => [$p => '/' . ($p === 'inicio' ? '' : str_replace('erp-modulos', 'erp/modulos', $p))])
                                ->all())
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->live()
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('activo')->label('Visible')->default(true)->inline(false),
                    ]),

                Forms\Components\Section::make('Encabezado')
                    ->description('Lo primero que se ve de la página.')
                    ->extraAttributes(['data-zona' => 'encabezado'])
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label(fn (Get $get): string => $get('slug') === 'inicio' ? 'Titular del hero' : 'Título')
                            ->required()->maxLength(150)->live(onBlur: true),
                        Forms\Components\Textarea::make('descripcion')
                            ->label('Texto de entrada')
                            ->rows(3)->maxLength(600)->live(onBlur: true),
                        Forms\Components\FileUpload::make('imagen')
                            ->label('Imagen de la página')
                            ->helperText(fn (Get $get): string => (self::IMAGEN_EN[$get('slug')] ?? 'Es la que sale al compartir el enlace.') . ' Es la única imagen de la página.')
                            ->image()->disk('public')->directory('sitio/paginas')
                            ->imagePreviewHeight('160')
                            ->openable()
                            ->live(),
                    ]),

                Forms\Components\Section::make('Secciones')
                    ->description('Cada bloque dice dónde va. Si eliges una sección, reemplaza el contenido por defecto de esa parte; sin sección, se pinta al final de la página.')
                    ->extraAttributes(['data-zona' => 'bloques'])
                    ->schema([
                        Forms\Components\Repeater::make('bloques')
                            ->hiddenLabel()
                            ->schema(self::camposBloque())
                            ->columns(2)
                            ->live()
                                                        ->itemLabel(fn (array $state): string => ($state['seccion'] ?? 'al final')
                                . ' · ' . (($state['titulo'] ?? '') ?: (($state['accion'] ?? '') ?: 'sin título')))
                            ->addActionLabel('Agregar bloque')
                            ->defaultItems(0)
                            ->collapsible()
                            ->collapsed()
                            ->reorderableWithButtons()
                            ->cloneable(),
                    ]),

                Forms\Components\Section::make('Planes')
                    ->description('Los precios que muestra esta página.')
                    ->extraAttributes(['data-zona' => 'planes'])
                    ->visible(fn (Get $get): bool => in_array($get('slug'), self::CON_PLANES, true))
                    ->schema([
                        Forms\Components\Repeater::make('planes')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Get $get): array {
                                $data['empresa_id']  = SitioPropio::empresaId();
                                $data['pagina_slug'] = $get('slug');

                                return $data;
                            })
                            ->schema([
                                Forms\Components\TextInput::make('nombre')->label('Nombre')->required()->maxLength(120),
                                Forms\Components\TextInput::make('precio_desde')->label('Desde (USD)')->numeric()->prefix('USD'),
                                Forms\Components\Textarea::make('descripcion')->label('Para quién es')->rows(2)->columnSpanFull(),
                                Forms\Components\TagsInput::make('incluye')
                                    ->label('Qué incluye')
                                    ->helperText('Escribe un punto y presiona Enter.')
                                    ->reorderable()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('nota')->label('Nota debajo del precio')->maxLength(200)->columnSpanFull(),
                                Forms\Components\Toggle::make('destacado')->label('Destacado'),
                                Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
                            ])
                            ->columns(2)
                            ->live()
                            ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? 'Plan nuevo')
                            ->addActionLabel('Agregar plan')
                            ->defaultItems(0)
                            ->collapsible()
                            ->collapsed(),
                    ]),

                Forms\Components\Section::make('Cómo se comparte')
                    ->description('Lo que muestran Google y WhatsApp al compartir el enlace. La imagen es la de la página.')
                    ->extraAttributes(['data-zona' => 'compartir'])
                    ->schema([
                        Forms\Components\TextInput::make('seo_titulo')
                            ->label('Título al compartir')
                            ->placeholder(fn (Get $get) => $get('titulo'))
                            ->maxLength(120)->live(onBlur: true),
                        Forms\Components\Textarea::make('seo_descripcion')
                            ->label('Frase al compartir')
                            ->helperText('Una o dos líneas. Si está vacía, se usa el texto de entrada.')
                            ->rows(2)->maxLength(300)->live(onBlur: true),
                    ]),
            ]),

            Forms\Components\Group::make()
                ->columnSpan(['default' => 1, 'xl' => 2])
                ->extraAttributes(['class' => 'xl:sticky xl:top-20 self-start'])
                ->schema([
                    Forms\Components\Placeholder::make('miniatura')
                        ->hiddenLabel()
                        ->content(fn (Get $get) => view('filament.admin.sitio.miniatura', self::datosMiniatura($get))),
                ]),
        ]);
    }

    /** Los campos de un bloque. Solo los que la web usa. */
    private static function camposBloque(): array
    {
        return [
            Forms\Components\Select::make('seccion')
                ->label('Dónde va')
                ->options(fn (Get $get): array => config('sitio.secciones.' . $get('../../slug'), []))
                ->placeholder('Al final de la página')
                ->live()
                ->native(false),
            Forms\Components\Select::make('tipo')
                ->label('Qué es')
                ->options([
                    'encabezado' => 'Encabezado de la sección (título, entrada, nota y botón)',
                    'tarjeta'    => 'Tarjeta (título, texto, apoyo y lista)',
                    'grupo'      => 'Título de grupo (agrupa los bloques que siguen)',
                    'nota'       => 'Nota destacada (fondo oscuro)',
                    'enlace'     => 'Enlace a otra parte',
                    'texto'      => 'Solo texto',
                    'galeria'    => 'Foto de la galería',
                ])
                ->default('tarjeta')
                ->required()
                ->live()
                ->native(false),
            Forms\Components\TextInput::make('titulo')->label('Título')->maxLength(150)->live(onBlur: true),
            Forms\Components\TextInput::make('apoyo')
                ->label(fn (Get $get): string => $get('tipo') === 'encabezado' ? 'Nota' : 'Línea de apoyo')
                ->maxLength(200)
                ->visible(fn (Get $get): bool => $get('tipo') !== 'galeria'),
            Forms\Components\Textarea::make('texto')
                ->label('Texto')->rows(3)->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('tipo') !== 'galeria'),
            Forms\Components\TextInput::make('accion')
                ->label('Texto del botón')
                ->maxLength(80)
                ->visible(fn (Get $get): bool => in_array($get('tipo'), ['encabezado', 'tarjeta', 'nota', 'enlace'], true)),
            Forms\Components\TextInput::make('enlace')
                ->label('Enlace')
                ->helperText('Una ruta del sitio (/erp, /contacto) o una dirección completa.')
                ->maxLength(300)
                ->visible(fn (Get $get): bool => $get('tipo') !== 'galeria'),
            Forms\Components\TagsInput::make('lista')
                ->label('Lista de puntos')
                ->helperText('Escribe un punto y presiona Enter.')
                ->reorderable()
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => in_array($get('tipo'), ['tarjeta', 'grupo', 'texto'], true)),
            Forms\Components\FileUpload::make('imagen')
                ->label('Foto')
                ->image()->disk('public')->directory('sitio/bloques')
                ->imagePreviewHeight('120')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => in_array($get('tipo'), ['galeria', 'tarjeta'], true)),
        ];
    }

    /** Lo que necesita la miniatura, tomado del formulario tal como está ahora. */
    private static function datosMiniatura(Get $get): array
    {
        $slug    = $get('slug');
        $bloques = array_values($get('bloques') ?? []);

        return [
            'slug'      => $slug,
            'titulo'    => $get('titulo'),
            'texto'     => $get('descripcion'),
            'imagen'    => self::urlImagen($get('imagen')),
            'compartir' => [
                'titulo' => $get('seo_titulo') ?: $get('titulo'),
                'texto'  => $get('seo_descripcion') ?: $get('descripcion'),
            ],
            'secciones' => config('sitio.secciones.' . $slug, []),
            'bloques'   => $bloques,
            // Para marcar la zona al enfocar un bloque: posición → sección.
            'mapa'      => array_map(fn (array $b) => $b['seccion'] ?? 'libres', $bloques),
            'planes'    => in_array($slug, self::CON_PLANES, true)
                ? array_values(array_map(fn ($p) => $p['nombre'] ?? 'Plan nuevo', $get('planes') ?? []))
                : null,
        ];
    }

    private static function urlImagen(mixed $estado): ?string
    {
        $archivo = is_array($estado) ? (array_values($estado)[0] ?? null) : $estado;

        if ($archivo instanceof TemporaryUploadedFile) {
            return $archivo->temporaryUrl();
        }

        return is_string($archivo) && $archivo !== '' ? Storage::disk('public')->url($archivo) : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->disk('public')
                    ->height(40)
                    ->defaultImageUrl(null)
                    ->tooltip(fn ($record) => $record->imagen ? 'Con imagen' : 'Sin imagen'),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Página')
                    ->formatStateUsing(fn (string $state): string => '/' . ($state === 'inicio' ? '' : str_replace('erp-modulos', 'erp/modulos', $state)))
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('titulo')->label('Título')->weight('semibold')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('bloques')
                    ->label('Bloques')
                    ->state(fn (SitioPagina $record): int => count($record->bloques ?? []))
                    ->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
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
