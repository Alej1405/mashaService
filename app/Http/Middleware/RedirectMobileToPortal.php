<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileToPortal
{
    /**
     * Patrones que identifican teléfonos (excluye tablets como iPad y Android tablets).
     * iPad no lleva "Mobile" en su UA. Tablets Android tampoco en la mayoría de casos.
     */
    private const PHONE_PATTERN = '/\b(Mobile|iPhone|iPod|Android.*Mobile|BlackBerry|IEMobile|Opera Mini|Windows Phone)\b/i';

    public function handle(Request $request, Closure $next): Response
    {
        // No redirigir si ya está en el portal móvil
        if (str_starts_with($request->path(), 'mobile')) {
            return $next($request);
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

        if (preg_match(self::PHONE_PATTERN, $userAgent)) {
            return redirect('/mobile');
        }

        return $next($request);
    }
}
