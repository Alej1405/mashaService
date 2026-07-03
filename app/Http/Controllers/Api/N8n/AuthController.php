<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TelegramSession;
use App\Modules\N8n\Actions\IniciarSesionTelegram;
use App\Modules\N8n\Queries\ModulosGestionablesDelUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación de la integración n8n (Telegram).
 * Solo /login es "público" (detrás de N8nGate); el resto exige token de sesión.
 */
class AuthController extends Controller
{
    /** Login por usuario/clave; captura el chat_id y abre sesión. */
    public function login(Request $request, IniciarSesionTelegram $iniciarSesion): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'chat_id' => ['required', 'string', 'max:64'],
        ]);

        $resultado = $iniciarSesion->handle($data['email'], $data['password'], $data['chat_id']);

        return response()->json($resultado, $resultado['ok'] ? 200 : 401);
    }

    /** Contexto de la sesión activa (usuario + empresa + módulos permitidos). */
    public function me(Request $request, ModulosGestionablesDelUsuario $modulos): JsonResponse
    {
        $session = $request->attributes->get('n8n_session');
        $user = $request->attributes->get('n8n_user');
        $empresa = $request->attributes->get('n8n_empresa');

        return response()->json([
            'ok' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'empresa' => $empresa ? [
                'id' => $empresa->id,
                'slug' => $empresa->slug,
                'name' => $empresa->name,
                'modulos' => $modulos->handle($user, $empresa),
            ] : null,
            'estado' => $session->estado,
            'expires_at' => $session->expires_at?->toIso8601String(),
        ]);
    }

    /** Elige la empresa activa (caso multiempresa). */
    public function selectEmpresa(Request $request, ModulosGestionablesDelUsuario $modulos): JsonResponse
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer'],
        ]);

        /** @var TelegramSession $session */
        $session = $request->attributes->get('n8n_session');
        $user = $request->attributes->get('n8n_user');

        // El super_admin puede elegir cualquier empresa activa; el resto, solo las
        // suyas (acceso con rol).
        $empresa = $user->hasRole('super_admin')
            ? Empresa::where('activo', true)->where('id', $data['empresa_id'])->first()
            : $user->empresasAcceso()->where('activo', true)
                ->where('empresas.id', $data['empresa_id'])->first();

        if (! $empresa) {
            throw ValidationException::withMessages([
                'empresa_id' => 'Esa empresa no está entre tus empresas disponibles.',
            ]);
        }

        $session->forceFill([
            'empresa_id' => $empresa->id,
            'estado' => 'activa',
        ])->save();

        return response()->json([
            'ok' => true,
            'empresa' => [
                'id' => $empresa->id,
                'slug' => $empresa->slug,
                'name' => $empresa->name,
                'rol' => $empresa->pivot->rol ?? ($user->hasRole('super_admin') ? 'super_admin' : null),
                'modulos' => $modulos->handle($user, $empresa),
            ],
        ]);
    }

    /** Cierra la sesión: elimina la fila (corta el token). */
    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('n8n_session')->delete();

        return response()->json(['ok' => true, 'mensaje' => 'Sesión cerrada.']);
    }
}
