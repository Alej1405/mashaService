<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioTestimonioResource\Pages;
use App\Filament\Cms\Resources\CmsTestimonialResource;

/**
 * Mismo contenido que el panel CMS, pero acotado al sitio propio y visible
 * desde /admin. El formulario y la tabla se heredan: no se duplica nada.
 */
class SitioTestimonioResource extends CmsTestimonialResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-chat-bubble-left';
    protected static ?string $navigationLabel        = 'Testimonios';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 13;
    protected static ?string $modelLabel             = 'Testimonio';
    protected static ?string $pluralModelLabel       = 'Testimonios';

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioTestimonio::route('/'),
            'create' => Pages\CreateSitioTestimonio::route('/create'),
            'edit'   => Pages\EditSitioTestimonio::route('/{record}/edit'),
        ];
    }
}
