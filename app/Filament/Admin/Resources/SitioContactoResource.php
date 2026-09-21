<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioContactoResource\Pages;
use App\Filament\Cms\Resources\CmsContactResource;

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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioContacto::route('/'),
            'create' => Pages\CreateSitioContacto::route('/create'),
            'edit'   => Pages\EditSitioContacto::route('/{record}/edit'),
        ];
    }
}
