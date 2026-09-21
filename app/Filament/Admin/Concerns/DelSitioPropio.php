<?php

namespace App\Filament\Admin\Concerns;

use App\Support\SitioPropio;
use Illuminate\Database\Eloquent\Builder;

/**
 * El panel /admin no tiene tenant, asi que el EmpresaScope no sabe a que
 * empresa filtrar. Este trait fija la empresa propia a mano: el recurso solo
 * ve y solo crea contenido del sitio de MashaCorp.
 *
 * Los recursos que lo usan deben declarar ademas:
 *     protected static ?string $tenantRelationshipName = null;
 * (una propiedad no se puede redefinir desde un trait).
 */
trait DelSitioPropio
{
    public static function getEloquentQuery(): Builder
    {
        $modelo = static::getModel();
        $tabla  = (new $modelo)->getTable();

        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where("{$tabla}.empresa_id", SitioPropio::empresa()?->id ?? 0);
    }

    /** Sin empresa propia creada, el grupo no aparece en el menú. */
    public static function canAccess(): bool
    {
        return SitioPropio::empresa() !== null;
    }
}
