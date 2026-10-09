<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioServicioResource\Pages;
use App\Filament\Cms\Resources\CmsServiceResource;

/**
 * Mismo contenido que el panel CMS, acotado al sitio propio y visible desde
 * /admin. El formulario y la tabla se heredan: no se duplica nada.
 */
class SitioServicioResource extends CmsServiceResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel        = 'Servicios';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';

    // Ya no se edita aquí: la web no lo usa o vive dentro de Páginas.
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int    $navigationSort         = 17;
    protected static ?string $modelLabel             = 'Servicio';
    protected static ?string $pluralModelLabel       = 'Servicios';

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioServicio::route('/'),
            'create' => Pages\CreateSitioServicio::route('/create'),
            'edit'   => Pages\EditSitioServicio::route('/{record}/edit'),
        ];
    }
}
