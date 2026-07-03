<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puerta de entrada a TODA la superficie /api/n8n/*.
 *
 * Controles (en orden): kill-switch global → secreto compartido → IP opcional.
 * Es lo que aísla a n8n del resto: si se desactiva el flag o se rota el secreto,
 * n8n queda fuera sin afectar ninguna otra API.
 */
class N8nGate
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Kill-switch: suspende SOLO la integración n8n.
        if (! config('n8n.enabled')) {
            return response()->json([
                'ok' => false,
                'error' => 'integracion_n8n_deshabilitada',
            ], 503);
        }

        // 2) Secreto compartido (control principal: la IP no es fiable con proxies).
        $secret = (string) config('n8n.secret');
        $provided = (string) $request->header('X-N8N-Secret', '');

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            return response()->json([
                'ok' => false,
                'error' => 'no_autorizado',
            ], 403);
        }

        // 3) Endurecimiento opcional por IP (solo si se configuró una lista).
        $allowed = config('n8n.allowed_ips', []);
        if (! empty($allowed) && ! in_array($request->ip(), $allowed, true)) {
            return response()->json([
                'ok' => false,
                'error' => 'ip_no_permitida',
            ], 403);
        }

        return $next($request);
    }
}
