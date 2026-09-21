<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioSeoResource\Pages;
use App\Models\SitioSeo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SitioSeoResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioSeo::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel        = 'SEO';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 5;
    protected static ?string $modelLabel             = 'Metadato';
    protected static ?string $pluralModelLabel       = 'SEO por ruta';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('ruta')
                    ->label('Ruta')
                    ->helperText('Tal como aparece en el sitio: /, /desarrollo, /laboratorio/mi-articulo')
                    ->required()->maxLength(160)->columnSpanFull(),
                Forms\Components\TextInput::make('titulo_meta')
                    ->label('Título')->maxLength(70)
                    ->helperText('Hasta 70 caracteres: más largo se corta en el buscador.')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('descripcion_meta')
                    ->label('Descripción')->rows(2)->maxLength(160)
                    ->helperText('Hasta 160 caracteres.')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('og_imagen')
                    ->label('Imagen para compartir')->image()->disk('public')->directory('sitio/seo')
                    ->imagePreviewHeight('80'),
                Forms\Components\Select::make('robots')
                    ->label('Indexación')
                    ->options([
                        'index,follow'     => 'Indexar y seguir enlaces',
                        'noindex,follow'   => 'No indexar, seguir enlaces',
                        'noindex,nofollow' => 'No indexar ni seguir',
                    ])
                    ->default('index,follow')
                    ->native(false),
                Forms\Components\KeyValue::make('json_ld')
                    ->label('Datos estructurados (JSON-LD)')
                    ->helperText('Lo que los buscadores y la IA leen para citarnos.')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ruta')
            ->columns([
                Tables\Columns\TextColumn::make('ruta')->label('Ruta')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('titulo_meta')->label('Título')->limit(45),
                Tables\Columns\TextColumn::make('descripcion_meta')->label('Descripción')->limit(50)->color('gray'),
                Tables\Columns\TextColumn::make('robots')->label('Indexación')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSitioSeo::route('/')];
    }
}
