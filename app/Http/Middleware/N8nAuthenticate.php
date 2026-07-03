<?php

namespace App\Http\Middleware;

use App\Models\TelegramSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida el token de sesión de Telegram (Bearer) contra telegram_sessions.
 * Deja disponible la sesión, el usuario y la empresa activa en el request para
 * los controladores. NO usa el guard del backend: la identidad n8n vive aparte.
 */
class N8nAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'ok' => false,
                'error' => 'sesion_requerida',
                'mensaje' => 'No hay sesión activa. Inicia sesión con tu usuario y clave.',
            ], 401);
        }

        $session = TelegramSession::query()
            ->where('token_hash', TelegramSession::hashToken($token))
            ->first();

        if (! $session || ! $session->estaVigente()) {
            return response()->json([
                'ok' => false,
                'error' => 'sesion_invalida',
                'mensaje' => 'Tu sesión expiró o no es válida. Inicia sesión de nuevo.',
            ], 401);
        }

        $session->forceFill(['last_used_at' => now()])->saveQuietly();

        // Contexto disponible para los controladores n8n.
        $request->attributes->set('n8n_session', $session);
        $request->attributes->set('n8n_user', $session->user);
        $request->attributes->set('n8n_empresa', $session->empresa);

        return $next($request);
    }
}
