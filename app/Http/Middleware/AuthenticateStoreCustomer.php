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

        return $next($request);
    }
}
