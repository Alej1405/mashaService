<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileToPortal
{
    /**
     * Teléfonos y tablets. El iPad no lleva "Mobile" en su user-agent y las
     * tablets Android tampoco, por eso van aparte: desde una tablet tampoco se
     * abre el ERP de escritorio.
     */
    private const MOVIL_PATTERN = '/\b(Mobile|iPhone|iPod|Android.*Mobile|BlackBerry|IEMobile|Opera Mini|Windows Phone)\b/i';
    private const TABLET_PATTERN = '/\b(iPad|Tablet|PlayBook|Silk|Kindle)\b|Android(?!.*Mobile)/i';

    /**
     * Lo único que se abre desde un celular o una tablet. El resto del ERP es de
     * escritorio y se redirige al portal móvil.
     *
     *   operaciones/…        el panel de bodega, que está hecho para esto
     *   i/…                  la ficha que abre el QR de la gaveta
     *   inventario/etiquetas las etiquetas para imprimir
     *   mobile/…             el portal móvil
     *   tienda/…             el portal de clientes, ya responsive
     */
    private const PERMITIDO_EN_MOVIL = ['operaciones', 'i/', 'inventario/etiquetas', 'mobile', 'tienda/'];

    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::PERMITIDO_EN_MOVIL as $prefijo) {
            if (str_starts_with($request->path(), $prefijo)) {
                return $next($request);
            }
        }

        // No redirigir peticiones AJAX/Livewire/JSON
        if ($request->ajax() || $request->expectsJson()) {
            return $next($request);
        }

        // Solo redirigir GET (no formularios POST)
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // No redirigir rutas técnicas
        if (in_array($request->path(), ['up', 'livewire/update', 'livewire/upload-file'])) {
            return $next($request);
        }

        $userAgent = $request->userAgent() ?? '';

        if (preg_match(self::MOVIL_PATTERN, $userAgent) || preg_match(self::TABLET_PATTERN, $userAgent)) {
            return redirect('/mobile');
        }

        return $next($request);
    }
}
