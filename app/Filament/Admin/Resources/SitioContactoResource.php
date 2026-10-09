<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioContactoResource\Pages;
use App\Filament\Cms\Resources\CmsContactResource;
use Filament\Forms;
use Filament\Forms\Form;


/**
 * Mismo contenido que el panel CMS, pero acotado al sitio propio y visible
 * desde /admin. El formulario y la tabla se heredan: no se duplica nada.
 */
class SitioContactoResource extends CmsContactResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-phone';
    protected static ?string $navigationLabel        = 'Contacto';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 11;
    protected static ?string $modelLabel             = 'Contacto';
    protected static ?string $pluralModelLabel       = 'Contacto';

        /** Solo lo que muestra mashaec.net: el pie y la sección de contacto. */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de contacto')->columns(2)->schema([
                Forms\Components\TextInput::make('email')->label('Correo')->email()->maxLength(150),
                Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->helperText('Ej: 0991234567')->maxLength(30),
                Forms\Components\TextInput::make('telefono')->label('Teléfono')->maxLength(30),
                Forms\Components\TextInput::make('direccion')->label('Ciudad o dirección')->helperText('Sale al pie: «© 2026 · Quito, Ecuador».')->maxLength(200),
            ]),
            Forms\Components\Section::make('Redes')->description('Las vacías no se muestran.')->columns(2)->schema([
                Forms\Components\TextInput::make('instagram')->label('Instagram')->url()->maxLength(255),
                Forms\Components\TextInput::make('linkedin')->label('LinkedIn')->url()->maxLength(255),
                Forms\Components\TextInput::make('facebook')->label('Facebook')->url()->maxLength(255),
                Forms\Components\TextInput::make('youtube')->label('YouTube')->url()->maxLength(255),
                Forms\Components\TextInput::make('tiktok')->label('TikTok')->url()->maxLength(255),
            ]),
            Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioContacto::route('/'),
            'create' => Pages\CreateSitioContacto::route('/create'),
            'edit'   => Pages\EditSitioContacto::route('/{record}/edit'),
        ];
    }
}
