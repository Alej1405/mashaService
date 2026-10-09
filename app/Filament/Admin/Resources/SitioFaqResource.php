<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioFaqResource\Pages;
use App\Filament\Cms\Resources\CmsFaqResource;

/**
 * Mismo contenido que el panel CMS, pero acotado al sitio propio y visible
 * desde /admin. El formulario y la tabla se heredan: no se duplica nada.
 */
class SitioFaqResource extends CmsFaqResource
{
    use DelSitioPropio;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel        = 'Preguntas';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';

    // Ya no se edita aquí: la web no lo usa o vive dentro de Páginas.
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int    $navigationSort         = 14;
    protected static ?string $modelLabel             = 'Pregunta';
    protected static ?string $pluralModelLabel       = 'Preguntas frecuentes';

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSitioFaq::route('/'),
            'create' => Pages\CreateSitioFaq::route('/create'),
            'edit'   => Pages\EditSitioFaq::route('/{record}/edit'),
        ];
    }
}
