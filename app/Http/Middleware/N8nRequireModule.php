<?php

namespace App\Http\Middleware;

use App\Modules\N8n\Queries\ModulosGestionablesDelUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que el usuario de la sesión tenga cierto módulo (plan ∩ rol) en la empresa
 * activa. Es la puerta por-módulo de la API n8n: el flujo de CMS pasa por
 * N8nRequireModule:marketing, el de tienda por :tienda, etc.
 */
class N8nRequireModule
{
    public function __construct(
        private readonly ModulosGestionablesDelUsuario $modulos,
    ) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->attributes->get('n8n_user');
        $empresa = $request->attributes->get('n8n_empresa');

        if (! $empresa) {
            return response()->json([
                'ok' => false,
                'error' => 'sin_empresa_activa',
                'mensaje' => 'Selecciona una empresa antes de continuar.',
            ], 409);
        }

        if (! in_array($module, $this->modulos->handle($user, $empresa), true)) {
            return response()->json([
                'ok' => false,
                'error' => 'sin_permiso_modulo',
                'mensaje' => 'Tu rol no tiene permiso para gestionar este módulo.',
            ], 403);
        }

        return $next($request);
    }
}
