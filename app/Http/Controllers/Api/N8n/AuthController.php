<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\TelegramSession;
use App\Modules\N8n\Actions\IniciarSesionTelegram;
use App\Modules\N8n\Actions\LigarTelegram;
use App\Modules\N8n\Actions\VerificarAccesoEmpresa;
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

    /**
     * Verifica credenciales y acceso a una empresa, sin abrir sesión de Telegram.
     * Lo usa n8n para el panel de administración de las herramientas de Link Cargo.
     */
    public function verificar(Request $request, VerificarAccesoEmpresa $verificar): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'empresa' => ['required', 'string', 'max:120'],
        ]);

        $resultado = $verificar->handle($data['email'], $data['password'], $data['empresa']);

        $status = match ($resultado['error'] ?? null) {
            null => 200,
            'sin_acceso' => 403,
            default => 401,
        };

        return response()->json($resultado, $status);
    }

    /**
     * El usuario compartió su contacto en el bot: liga el chat al usuario con ese
     * teléfono para que reciba avisos. No abre sesión; para gestionar se inicia sesión.
     * n8n solo llama aquí si el contacto compartido es el del propio remitente.
     */
    public function ligarTelefono(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telefono' => ['required', 'string', 'max:20'],
            'chat_id'  => ['required', 'string', 'max:64'],
        ]);

        $resultado = app(LigarTelegram::class)->porTelefono($data['telefono'], $data['chat_id']);

        return response()->json($resultado, $resultado['ok'] ? 200 : 404);
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
