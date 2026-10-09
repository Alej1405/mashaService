<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioHeroResource\Pages;
use App\Filament\Cms\Resources\CmsHeroResource;

/**
 * Mismo contenido que el panel CMS, pero acotado al sitio propio y visible
 * desde /admin. El formulario y la tabla se heredan: no se duplica nada.
 */
class SitioHeroResource extends CmsHeroResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-photo';
    protected static ?string $navigationLabel        = 'Hero del inicio';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';

    // Ya no se edita aquí: la web no lo usa o vive dentro de Páginas.
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int    $navigationSort         = 10;
    protected static ?string $modelLabel             = 'Hero';
    protected static ?string $pluralModelLabel       = 'Hero del inicio';

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioHero::route('/'),
            'create' => Pages\CreateSitioHero::route('/create'),
            'edit'   => Pages\EditSitioHero::route('/{record}/edit'),
        ];
    }
}
