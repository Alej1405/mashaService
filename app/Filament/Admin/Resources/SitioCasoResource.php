<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioCasoResource\Pages;
use App\Models\SitioCaso;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SitioCasoResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioCaso::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel        = 'Casos';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 2;
    protected static ?string $modelLabel             = 'Caso';
    protected static ?string $pluralModelLabel       = 'Casos del portafolio';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ficha del caso')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('titulo')
                        ->label('Título')->required()->maxLength(150)->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->helperText('Si lo dejas vacío se genera del título.')
                        ->maxLength(120),
                    Forms\Components\Select::make('servicio')
                        ->label('Servicio')
                        ->options(fn (): array => array_combine(config('sitio.servicios'), config('sitio.servicios')))
                        ->default('desarrollo')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('cliente')->label('Cliente')->maxLength(150),
                    Forms\Components\TextInput::make('sector')->label('Sector')->maxLength(100),
                    Forms\Components\Textarea::make('resumen')
                        ->label('Resumen')->rows(2)->maxLength(400)->columnSpanFull(),
                    Forms\Components\TextInput::make('enlace_sitio')
                        ->label('Enlace al sitio del cliente')->url()->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Qué pasó')
                ->description('Problema, solución y resultado: es lo que el visitante expande sin salir de la página.')
                ->schema([
                    Forms\Components\Textarea::make('problema')->label('Problema')->rows(3)->columnSpanFull(),
                    Forms\Components\Textarea::make('solucion')->label('Solución')->rows(3)->columnSpanFull(),
                    Forms\Components\Textarea::make('resultado')->label('Resultado')->rows(3)->columnSpanFull(),
                    Forms\Components\Repeater::make('metricas')
                        ->label('Métricas')
                        ->schema([
                            Forms\Components\TextInput::make('etiqueta')->label('Qué se midió')->required()->maxLength(80),
                            Forms\Components\TextInput::make('valor')->label('Valor')->required()->maxLength(40),
                        ])
                        ->columns(2)
                        ->addActionLabel('Agregar métrica')
                        ->defaultItems(0)
                        ->collapsible()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Imágenes')
                ->schema([
                    Forms\Components\FileUpload::make('imagen_portada')
                        ->label('Portada')->image()->disk('public')->directory('sitio/casos')
                        ->imagePreviewHeight('120')->columnSpanFull(),
                    Forms\Components\Repeater::make('imagenes')
                        ->label('Galería')
                        ->relationship()
                        ->schema([
                            Forms\Components\FileUpload::make('imagen')
                                ->label('Imagen')->image()->disk('public')->directory('sitio/casos')
                                ->required()->columnSpanFull(),
                            Forms\Components\TextInput::make('texto_alt')
                                ->label('Texto alternativo')
                                ->helperText('Qué se ve en la foto. Lo lee un lector de pantalla y también la IA.')
                                ->maxLength(200),
                            Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
                        ])
                        ->columns(2)
                        ->orderColumn('sort_order')
                        ->addActionLabel('Agregar imagen')
                        ->defaultItems(0)
                        ->collapsible()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Publicación')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('publicable')
                        ->label('Autorizado por el cliente')
                        ->helperText('Sin esto el caso no sale en la API, aunque esté visible.')
                        ->default(false),
                    Forms\Components\Toggle::make('destacado')
                        ->label('Destacado')
                        ->helperText('El destacado abre expandido en el sitio.'),
                    Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
                    Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen_portada')->label('')->disk('public'),
                Tables\Columns\TextColumn::make('titulo')->label('Caso')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('cliente')->label('Cliente')->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('servicio')->label('Servicio')->badge(),
                Tables\Columns\IconColumn::make('publicable')->label('Autorizado')->boolean(),
                Tables\Columns\IconColumn::make('destacado')->label('Destacado')->boolean(),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('servicio')
                    ->options(fn (): array => array_combine(config('sitio.servicios'), config('sitio.servicios'))),
                Tables\Filters\TernaryFilter::make('publicable')->label('Autorizado por el cliente'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioCasos::route('/'),
            'create' => Pages\CreateSitioCaso::route('/create'),
            'edit'   => Pages\EditSitioCaso::route('/{record}/edit'),
        ];
    }
}
