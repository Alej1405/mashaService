<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStoreCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('portal_customer_id')) {
            return redirect()->route('portal.login', ['slug' => $request->route('slug')]);
        }

        // La sesión es de un cliente de UNA tienda: con otro slug no ve ni pide nada ajeno.
        $deEstaTienda = \App\Models\Customer::withoutGlobalScopes()
            ->whereKey($request->session()->get('portal_customer_id'))
            ->whereHas('empresa', fn ($q) => $q->where('slug', $request->route('slug')))
            ->exists();

        if (! $deEstaTienda) {
            $request->session()->forget('portal_customer_id');

            return redirect()->route('portal.login', ['slug' => $request->route('slug')]);
        }

        return $next($request);
    }
}
