<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sesión para las pantallas que se abren desde un QR.
 *
 * El middleware `auth` de Laravel redirige a una ruta llamada 'login', que en
 * este ERP no existe: los accesos son de Filament (/admin/login, /app/login).
 * Sin esto, escanear sin sesión devolvía un 500 en vez de mandar al login.
 *
 * Guarda a dónde iba para volver ahí después de entrar: quien escanea una
 * gaveta quiere ver esa gaveta, no un panel genérico.
 */
class AutenticarEnBodega
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        session(['url.intended' => $request->fullUrl()]);

        $login = \Illuminate\Support\Facades\Route::has('filament.basic.auth.login')
            ? route('filament.basic.auth.login')
            : route('filament.admin.auth.login');

        return redirect()->guest($login);
    }
}
