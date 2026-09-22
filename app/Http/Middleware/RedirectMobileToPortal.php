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

    /**
     * A dónde va quien entra desde un celular o una tablet.
     *
     * Si la empresa del usuario abre el panel de bodega, ahí: es el único
     * pensado para trabajar de pie y con una mano. El portal móvil queda como
     * respaldo para quien no tiene inventario.
     */
    private function destinoEnMovil(): string
    {
        $usuario = auth()->user();

        if (! $usuario) {
            return '/mobile';
        }

        try {
            $panel = \Filament\Facades\Filament::getPanel('operaciones');

            if ($usuario->getTenants($panel)->isNotEmpty()) {
                return '/operaciones';
            }
        } catch (\Throwable) {
            // Si el panel no existe o el usuario no resuelve empresa, portal móvil.
        }

        return '/mobile';
    }

    /** Login, cierre de sesión y recuperación de contraseña, de cualquier panel. */
    private function esPantallaDeAcceso(Request $request): bool
    {
        $nombre = $request->route()?->getName() ?? '';

        if (str_contains($nombre, '.auth.')) {
            return true;
        }

        $ruta = $request->path();

        foreach (['login', 'logout', 'password-reset', 'password/reset'] as $final) {
            if ($ruta === $final || str_ends_with($ruta, '/' . $final)) {
                return true;
            }
        }

        return false;
    }

    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::PERMITIDO_EN_MOVIL as $prefijo) {
            if (str_starts_with($request->path(), $prefijo)) {
                return $next($request);
            }
        }

        // Las pantallas de acceso nunca se redirigen. El login de usuarios vive
        // en /app/login, que es un panel de escritorio: sin esta excepción,
        // entrar desde el celular mandaba al portal móvil antes de poder
        // escribir la contraseña.
        if ($this->esPantallaDeAcceso($request)) {
            return $next($request);
        }

        // No redirigir peticiones AJAX/Livewire/JSON. La navegación SPA de
        // Livewire (wire:navigate) sí se redirige: viaja como fetch, pero para
        // el usuario es cambiar de pantalla, y sin esto el ERP de escritorio se
        // colaba en el celular después del login.
        if (($request->ajax() || $request->expectsJson()) && ! $request->hasHeader('X-Livewire-Navigate')) {
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
            return redirect($this->destinoEnMovil());
        }

        return $next($request);
    }
}
