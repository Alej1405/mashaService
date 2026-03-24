<?php

namespace App\Http\Middleware;

use App\Models\StoreCustomer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() instanceof StoreCustomer) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Verificar que el cliente pertenece a esta tienda
        $empresa = app('store.empresa');
        if ($request->user()->empresa_id !== $empresa->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}
