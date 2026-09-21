<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioLogoResource\Pages;
use App\Filament\Cms\Resources\CmsClientLogoResource;

/**
 * Mismo contenido que el panel CMS, pero acotado al sitio propio y visible
 * desde /admin. El formulario y la tabla se heredan: no se duplica nada.
 */
class SitioLogoResource extends CmsClientLogoResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel        = 'Logos';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 12;
    protected static ?string $modelLabel             = 'Logo';
    protected static ?string $pluralModelLabel       = 'Logos de clientes';

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioLogo::route('/'),
            'create' => Pages\CreateSitioLogo::route('/create'),
            'edit'   => Pages\EditSitioLogo::route('/{record}/edit'),
        ];
    }
}
