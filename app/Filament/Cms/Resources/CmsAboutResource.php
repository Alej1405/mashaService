<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\CmsAboutResource\Pages;
use App\Models\CmsAbout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsAboutResource extends Resource
{
    protected static ?string $model = CmsAbout::class;

    protected static ?string $tenantRelationshipName = 'cmsAbouts';
    protected static ?string $navigationIcon         = 'heroicon-o-information-circle';
    protected static ?string $navigationLabel        = 'Nosotros';
    protected static ?string $navigationGroup        = 'Contenido Web';
    protected static ?int    $navigationSort         = 2;
    protected static ?string $modelLabel             = 'Nosotros';
    protected static ?string $pluralModelLabel       = 'Nosotros';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información principal')
                ->icon('heroicon-o-building-office')
                ->schema([
                    Forms\Components\TextInput::make('titulo')
                        ->label('Título de la sección')
                        ->required()
                        ->maxLength(150)
                        ->placeholder('¿Quiénes somos?')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción general')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('imagen')
                        ->label('Imagen representativa')
                        ->image()
                        ->disk('public')
                        ->directory('cms/about')
                        ->imagePreviewHeight('120')
                        
                        ->helperText('Recomendado: 800×600 px.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('¿Por qué nosotros?')
                ->description('Razones clave que diferencian a tu empresa.')
                ->icon('heroicon-o-star')
                ->schema([
                    Forms\Components\Repeater::make('por_que_nosotros')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('icono')
                                ->label('Ícono (emoji o heroicon)')
                                ->maxLength(60)
                                ->placeholder('⭐'),
                            Forms\Components\TextInput::make('titulo')
                                ->label('Título')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\Textarea::make('descripcion')
                                ->label('Descripción')
                                ->rows(2)
                                ->maxLength(300)
                                ->columnSpanFull(),
                        ])
                        ->addActionLabel('Agregar razón')
                        ->defaultItems(0)
                        ->columns(2)
                        ->collapsible()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Números / Estadísticas')
                ->description('Cifras que demuestran tu experiencia.')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\Repeater::make('numeros')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('valor')
                                ->label('Valor')
                                ->required()
                                ->maxLength(30)
                                ->placeholder('500+'),
                            Forms\Components\TextInput::make('etiqueta')
                                ->label('Etiqueta')
                                ->required()
                                ->maxLength(80)
                                ->placeholder('Clientes satisfechos'),
                        ])
                        ->addActionLabel('Agregar estadística')
                        ->defaultItems(0)
                        ->columns(2)
                        ->collapsible()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Toggle::make('activo')
                ->label('Visible en el sitio')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->height(40)->width(64),
                Tables\Columns\TextColumn::make('titulo')->label('Título')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción')->limit(80)->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsAbouts::route('/'),
            'create' => Pages\CreateCmsAbout::route('/create'),
            'edit'   => Pages\EditCmsAbout::route('/{record}/edit'),
        ];
    }
}
