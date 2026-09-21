<?php

namespace App\Shared\Actions;

use App\Models\Customer;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\Cache;

#[Documentado(
    grupo: 'CMS',
    descripcion: 'Invalida la caché de la API CMS de un punto de venta (ficha + listado) para que los cambios del portal del cliente se reflejen al instante.',
    tipo: 'action',
)]
final class OlvidarCacheCmsPunto
{
    /**
     * La API CMS (CmsController) cachea las respuestas 10 min. Sin invalidar, un cambio
     * del cliente en el portal (colores, galería, horario, menú…) no se vería hasta que
     * expire el TTL. Estas son las MISMAS claves que arma CmsController::puntoVenta/
     * puntosVenta; si allá cambian, acá también.
     *
     * Olvida el listado de la empresa. Sin slug de empresa no hay clave que olvidar.
     */
    public function handle(Customer $customer): void
    {
        $empresaSlug = $customer->empresa?->slug;

        if (! $empresaSlug) {
            return;
        }

        Cache::forget("cms:{$empresaSlug}:puntos-venta");
    }
}
