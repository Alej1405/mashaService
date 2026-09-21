<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioNavegacionResource\Pages;
use App\Models\SitioNavegacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SitioNavegacionResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioNavegacion::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-bars-3';
    protected static ?string $navigationLabel        = 'Navegación';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 4;
    protected static ?string $modelLabel             = 'Ítem de navegación';
    protected static ?string $pluralModelLabel       = 'Navegación';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('etiqueta')->label('Etiqueta')->required()->maxLength(60),
                Forms\Components\TextInput::make('ruta')->label('Ruta')->required()->maxLength(160)->placeholder('/desarrollo'),
                Forms\Components\Select::make('ubicacion')
                    ->label('Ubicación')
                    ->options([
                        'superior' => 'Menú superior',
                        'inferior' => 'Barra inferior (móvil)',
                        'pie'      => 'Pie de página',
                    ])
                    ->default('superior')
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('dispositivo')
                    ->label('Se muestra en')
                    ->options(['ambos' => 'Ambos', 'movil' => 'Solo móvil', 'escritorio' => 'Solo escritorio'])
                    ->default('ambos')
                    ->native(false),
                Forms\Components\TextInput::make('icono')
                    ->label('Ícono')
                    ->helperText('Necesario en la barra inferior del móvil.')
                    ->maxLength(60),
                Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
                Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->groups(['ubicacion'])
            ->columns([
                Tables\Columns\TextColumn::make('etiqueta')->label('Etiqueta')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('ruta')->label('Ruta')->color('gray'),
                Tables\Columns\TextColumn::make('ubicacion')->label('Ubicación')->badge(),
                Tables\Columns\TextColumn::make('dispositivo')->label('Pantalla')->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ubicacion')->options([
                    'superior' => 'Menú superior',
                    'inferior' => 'Barra inferior (móvil)',
                    'pie'      => 'Pie de página',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSitioNavegacion::route('/')];
    }
}
