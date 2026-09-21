<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioNosotrosResource\Pages;
use App\Filament\Cms\Resources\CmsAboutResource;

/**
 * Mismo contenido que el panel CMS, acotado al sitio propio y visible desde
 * /admin. El formulario y la tabla se heredan: no se duplica nada.
 */
class SitioNosotrosResource extends CmsAboutResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-identification';
    protected static ?string $navigationLabel        = 'Nosotros';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 16;
    protected static ?string $modelLabel             = 'Nosotros';
    protected static ?string $pluralModelLabel       = 'Nosotros';

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioNosotros::route('/'),
            'create' => Pages\CreateSitioNosotros::route('/create'),
            'edit'   => Pages\EditSitioNosotros::route('/{record}/edit'),
        ];
    }
}
