<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateCmsApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug  = $request->route('slug');
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'Token requerido.'], 401);
        }

        $pat = PersonalAccessToken::findToken($token);

        if (! $pat || $pat->tokenable_type !== Empresa::class) {
            return response()->json(['error' => 'Token inválido.'], 401);
        }

        $empresa = Empresa::where('slug', $slug)->where('activo', true)->first();

        if (! $empresa || $pat->tokenable_id !== $empresa->id) {
            return response()->json(['error' => 'Token no autorizado para esta empresa.'], 403);
        }

        $pat->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
