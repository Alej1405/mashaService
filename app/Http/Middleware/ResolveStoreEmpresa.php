<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStoreEmpresa
{
    public function handle(Request $request, Closure $next): Response
    {
        $empresa = Empresa::where('slug', $request->route('empresa_slug'))->first();

        if (!$empresa) {
            return response()->json(['message' => 'Tienda no encontrada'], 404);
        }

        app()->instance('store.empresa', $empresa);
        $request->merge(['_empresa_id' => $empresa->id]);

        return $next($request);
    }
}
