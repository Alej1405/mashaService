<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\CmsHeroResource\Pages;
use App\Models\CmsHero;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsHeroResource extends Resource
{
    protected static ?string $model = CmsHero::class;

    protected static ?string $tenantRelationshipName = 'cmsHeroes';
    protected static ?string $navigationIcon         = 'heroicon-o-photo';
    protected static ?string $navigationLabel        = 'Hero / Portada';
    protected static ?string $navigationGroup        = 'Contenido Web';
    protected static ?int    $navigationSort         = 1;
    protected static ?string $modelLabel             = 'Hero';
    protected static ?string $pluralModelLabel       = 'Hero / Portada';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Texto principal')
                ->description('El mensaje central de tu página de inicio.')
                ->icon('heroicon-o-megaphone')
                ->schema([
                    Forms\Components\TextInput::make('titulo')
                        ->label('Título principal')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('La solución que tu empresa necesita')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('subtitulo')
                        ->label('Subtítulo')
                        ->maxLength(200)
                        ->placeholder('Frase de apoyo breve y memorable')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción / párrafo')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Llamada a la acción (CTA)')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->schema([
                    Forms\Components\TextInput::make('cta_texto')
                        ->label('Texto del botón')
                        ->maxLength(60)
                        ->placeholder('Contáctanos')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('cta_url')
                        ->label('URL del botón')
                        ->url()
                        ->maxLength(300)
                        ->placeholder('https://...')
                        ->columnSpan(2),
                ])->columns(3),

            Forms\Components\Section::make('Imagen de fondo')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('imagen')
                        ->label('Imagen hero')
                        ->image()
                        ->disk('public')
                        ->directory('cms/hero')
                        ->imagePreviewHeight('160')
                        
                        ->helperText('Recomendado: 1920×1080 px, JPG o WebP. Máx 5 MB.')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('activo')
                        ->label('Visible en el sitio')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')
                    ->label('')
                    ->height(48)
                    ->width(80)
                    ->defaultImageUrl(asset('img/placeholder.png')),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('subtitulo')
                    ->label('Subtítulo')
                    ->limit(60)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('cta_texto')
                    ->label('CTA')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsHeroes::route('/'),
            'create' => Pages\CreateCmsHero::route('/create'),
            'edit'   => Pages\EditCmsHero::route('/{record}/edit'),
        ];
    }
}
