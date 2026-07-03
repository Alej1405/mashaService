<?php

namespace App\Modules\N8n\Actions;

use App\Models\Empresa;
use App\Models\TelegramSession;
use App\Models\User;
use App\Modules\N8n\Queries\ModulosGestionablesDelUsuario;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Inicia la sesión de un usuario del ERP desde Telegram (vía n8n).
 * Verifica usuario/clave, captura dinámicamente el chat_id y crea/renueva la
 * sesión con un token propio. Nada hardcodeado.
 */
#[Documentado(
    grupo: 'Integración n8n',
    descripcion: 'Autentica al usuario del ERP por usuario/clave desde Telegram y crea la sesión con token propio.',
    tipo: 'action',
)]
final class IniciarSesionTelegram
{
    public function __construct(
        private readonly ModulosGestionablesDelUsuario $modulosGestionables,
    ) {}

    /**
     * @return array{ok:bool,error?:string,mensaje?:string,token?:string,user?:array,empresa?:?array,empresas?:array,needs_empresa_selection?:bool,expires_at?:string}
     */
    public function handle(string $email, string $password, string $chatId): array
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return [
                'ok' => false,
                'error' => 'credenciales_invalidas',
                'mensaje' => 'Usuario o clave incorrectos.',
            ];
        }

        // Empresas disponibles: el super_admin accede a TODAS (acceso global);
        // el resto, solo donde tiene acceso con rol (multitenancy).
        $empresas = $user->hasRole('super_admin')
            ? Empresa::where('activo', true)->orderBy('name')->get()
            : $user->empresasAcceso()->where('activo', true)->get();

        if ($empresas->isEmpty()) {
            return [
                'ok' => false,
                'error' => 'sin_empresa',
                'mensaje' => 'No hay una empresa activa disponible para tu usuario.',
            ];
        }

        $empresaSeleccionada = $empresas->count() === 1 ? $empresas->first() : null;

        // Token propio: el texto plano solo viaja a n8n una vez; se guarda hasheado.
        $token = Str::random(64);
        $expiresAt = now()->addMinutes((int) config('n8n.session_ttl', 720));

        // Un chat = una sesión. Un nuevo login reemplaza la anterior.
        TelegramSession::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'user_id' => $user->id,
                'empresa_id' => $empresaSeleccionada?->id,
                'token_hash' => TelegramSession::hashToken($token),
                'estado' => $empresaSeleccionada ? 'activa' : 'eligiendo_empresa',
                'expires_at' => $expiresAt,
                'last_used_at' => now(),
            ],
        );

        return [
            'ok' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'empresa' => $empresaSeleccionada ? $this->empresaPayload($user, $empresaSeleccionada) : null,
            'empresas' => $empresas->map(fn ($e) => $this->empresaPayload($user, $e))->values()->all(),
            'needs_empresa_selection' => $empresaSeleccionada === null,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function empresaPayload(User $user, $empresa): array
    {
        return [
            'id' => $empresa->id,
            'slug' => $empresa->slug,
            'name' => $empresa->name,
            'rol' => $empresa->pivot->rol ?? ($user->hasRole('super_admin') ? 'super_admin' : null),
            'modulos' => $this->modulosGestionables->handle($user, $empresa),
        ];
    }
}
