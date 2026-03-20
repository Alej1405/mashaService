<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Impide que usuarios con rol 'marketing' accedan a paneles distintos
 * al panel básico (mailing). Los redirige a /app/{slug}.
 */
class EnsureNotMarketingRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->hasRole('marketing')) {
            // Obtener slug desde el parámetro de ruta del tenant
            $slug = $request->route('tenant') ?? '';
            return redirect("/app/{$slug}");
        }

        return $next($request);
    }
}
