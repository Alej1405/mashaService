<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioInvestigacionResource\Pages;
use App\Models\SitioInvestigacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * El catálogo del laboratorio. Cada investigación tiene su página en
 * mashaec.net/laboratorio/investigaciones/<slug>, con su texto, su SEO y la
 * herramienta para usar el microservicio ahí mismo.
 */
class SitioInvestigacionResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioInvestigacion::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-beaker';
    protected static ?string $navigationLabel        = 'Laboratorio · Investigaciones';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 6;
    protected static ?string $modelLabel             = 'Investigación';
    protected static ?string $pluralModelLabel       = 'Investigaciones';

    /** Las herramientas que la web sabe montar. Una nueva necesita su componente en el front. */
    public const HERRAMIENTAS = [
        'arana'       => 'Araña de búsqueda',
        'formulacion' => 'Formulación y costeo',
        'clasificador' => 'Clasificador arancelario',
        'ninguna'     => 'Ninguna (solo texto)',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('La investigación')->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')->label('Título')->required()->maxLength(120),
                Forms\Components\TextInput::make('slug')
                    ->label('En la dirección')->prefix('/laboratorio/investigaciones/')
                    ->required()->maxLength(80)->alphaDash(),
                Forms\Components\Textarea::make('resumen')->label('Resumen')->helperText('Una o dos líneas: se ve en la lista del laboratorio.')->rows(2)->maxLength(300)->columnSpanFull(),
                Forms\Components\Textarea::make('para_quien')->label('Para quién es')->rows(2)->maxLength(300)->columnSpanFull(),
                Forms\Components\TagsInput::make('stack')->label('Con qué está hecho')->helperText('Ej: Python, FastAPI. Enter para agregar.'),
                Forms\Components\Select::make('estado')->label('Estado')
                    ->options(['en_vivo' => 'En vivo', 'en_desarrollo' => 'En desarrollo'])->default('en_vivo')->native(false),
                Forms\Components\RichEditor::make('cuerpo')->label('Explicación')
                    ->helperText('Para desarrolladores: qué hace, cómo está hecho, qué le falta.')
                    ->toolbarButtons(['h2', 'h3', 'bold', 'italic', 'link', 'bulletList', 'orderedList', 'codeBlock', 'blockquote'])
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('La herramienta')->columns(2)->schema([
                Forms\Components\Select::make('herramienta')->label('Qué se puede usar en la página')
                    ->options(self::HERRAMIENTAS)->default('ninguna')->required()->native(false),
                Forms\Components\TextInput::make('servicio_url')->label('Dirección del microservicio')
                    ->url()->placeholder('https://arana.srv1666598.hstgr.cloud')->maxLength(255),
            ]),
            Forms\Components\Section::make('Cómo se comparte')
                ->description('Lo que muestran Google, LinkedIn y WhatsApp al compartir el enlace.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('seo_titulo')->label('Título al compartir')->maxLength(120),
                    Forms\Components\FileUpload::make('imagen')->label('Imagen al compartir')
                        ->helperText('1200 × 630 px.')
                        ->image()->disk('public')->directory('sitio/investigaciones')->imagePreviewHeight('120'),
                    Forms\Components\Textarea::make('seo_descripcion')->label('Frase al compartir')->rows(2)->maxLength(300)->columnSpanFull(),
                ]),
            Forms\Components\Toggle::make('activo')->label('Publicada')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('titulo')->label('Investigación')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('herramienta')->label('Herramienta')
                    ->formatStateUsing(fn (string $state) => self::HERRAMIENTAS[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('estado')->label('Estado')
                    ->formatStateUsing(fn (string $state) => $state === 'en_vivo' ? 'En vivo' : 'En desarrollo')
                    ->color(fn (string $state) => $state === 'en_vivo' ? 'success' : 'gray')->badge(),
                Tables\Columns\ToggleColumn::make('activo')->label('Publicada'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioInvestigaciones::route('/'),
            'create' => Pages\CreateSitioInvestigacion::route('/create'),
            'edit'   => Pages\EditSitioInvestigacion::route('/{record}/edit'),
        ];
    }
}
