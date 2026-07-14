<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\CmsClientLogoResource\Pages;
use App\Models\CmsClientLogo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CmsClientLogoResource extends Resource
{
    protected static ?string $model = CmsClientLogo::class;

    protected static ?string $tenantRelationshipName = 'cmsClientLogos';
    protected static ?string $navigationIcon         = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel        = 'Logos de Clientes';
    protected static ?string $navigationGroup        = 'Contenido Web';
    protected static ?int    $navigationSort         = 7;
    protected static ?string $modelLabel             = 'Logo';
    protected static ?string $pluralModelLabel       = 'Logos de clientes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')->label('Nombre del cliente / empresa')->required()->maxLength(150)->columnSpanFull(),
            Forms\Components\FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('cms/clients')
                ->imagePreviewHeight('80')->helperText('PNG con fondo transparente recomendado.')->columnSpanFull(),
            Forms\Components\TextInput::make('url')->label('Sitio web del cliente (opcional)')->url()->maxLength(500)->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
            Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->label('Logo')->height(36),
                Tables\Columns\TextColumn::make('nombre')->label('Cliente')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('url')->label('Web')->limit(40)->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsClientLogos::route('/'),
            'create' => Pages\CreateCmsClientLogo::route('/create'),
            'edit'   => Pages\EditCmsClientLogo::route('/{record}/edit'),
        ];
    }
}
