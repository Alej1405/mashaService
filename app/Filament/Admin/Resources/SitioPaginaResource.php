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
                        ->label('Imagen')->image()->disk('public')->directory('sitio/paginas')
                        ->imagePreviewHeight('80'),
                    Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
                ]),

            Forms\Components\Section::make('Bloques')
                ->description('Cada bloque es una tarjeta o un apartado dentro de la página.')
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('bloques')
                        ->label('Bloques de contenido')
                        ->schema([
                            Forms\Components\TextInput::make('titulo')->label('Título')->maxLength(150),
                            Forms\Components\TextInput::make('icono')->label('Ícono (emoji o heroicon)')->maxLength(60),
                            Forms\Components\Textarea::make('texto')->label('Texto')->rows(3)->columnSpanFull(),
                            Forms\Components\FileUpload::make('imagen')
                                ->label('Imagen')->image()->disk('public')->directory('sitio/bloques')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state): ?string => $state['titulo'] ?? null)
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
