<?php

namespace App\Modules\N8n\Actions;

use App\Models\Empresa;
use App\Models\User;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\Hash;

/**
 * Verifica usuario/clave y que el usuario tenga acceso a una empresa, sin crear sesión
 * ni ligar Telegram. La usa el panel de administración de las herramientas de Link Cargo
 * (vía n8n): el ERP decide quién entra; n8n solo emite el token del panel.
 *
 * A diferencia de IniciarSesionTelegram, no toca TelegramSession ni el chat del usuario:
 * un inicio de sesión en un panel web no debe cambiar a dónde le llegan los avisos.
 */
#[Documentado(
    grupo: 'Integración n8n',
    descripcion: 'Verifica credenciales y acceso a una empresa sin crear sesión de Telegram (paneles externos).',
    tipo: 'action',
)]
final class VerificarAccesoEmpresa
{
    /**
     * @return array{ok:bool,error?:string,mensaje?:string,user?:array,empresa?:array}
     */
    public function handle(string $email, string $password, string $empresaSlug): array
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return ['ok' => false, 'error' => 'credenciales_invalidas', 'mensaje' => 'Usuario o clave incorrectos.'];
        }

        // Mismo criterio que el bot: el super_admin entra a todas; el resto, solo a las suyas.
        $empresa = $user->hasRole('super_admin')
            ? Empresa::where('activo', true)->where('slug', $empresaSlug)->first()
            : $user->empresasAcceso()->where('activo', true)->where('empresas.slug', $empresaSlug)->first();

        if (! $empresa) {
            return ['ok' => false, 'error' => 'sin_acceso', 'mensaje' => 'Tu usuario no tiene acceso a esta empresa.'];
        }

        return [
            'ok' => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'empresa' => [
                'id' => $empresa->id,
                'slug' => $empresa->slug,
                'name' => $empresa->name,
                'rol' => $empresa->pivot->rol ?? ($user->hasRole('super_admin') ? 'super_admin' : null),
            ],
        ];
    }
}
