<?php

namespace App\Modules\N8n\Actions;

use App\Models\Empresa;
use App\Models\User;
use App\Shared\Attributes\Documentado;

/**
 * Liga un chat de Telegram a un usuario del ERP. Pasa por dos caminos:
 * el usuario comparte su contacto (Telegram entrega el número y se compara con
 * `users.telefono`) o inicia sesión con email y clave en el bot.
 * Un chat pertenece a una sola persona; si estaba en otro usuario, se mueve.
 */
#[Documentado(
    grupo: 'Integración n8n',
    descripcion: 'Liga el chat de Telegram al usuario, por su teléfono registrado o al iniciar sesión.',
    tipo: 'action',
)]
final class LigarTelegram
{
    /** @return array{ok:bool,error?:string,mensaje?:string,user?:array,empresas?:array} */
    public function porTelefono(string $telefono, string $chatId): array
    {
        $user = User::where('telefono', self::normalizar($telefono))->first();

        if (! $user) {
            return [
                'ok' => false,
                'error' => 'telefono_no_registrado',
                'mensaje' => 'Ese número no está registrado en el ERP. Pide al administrador que lo agregue en tu usuario, con el formato +593…',
            ];
        }

        $this->ligar($user, $chatId);

        $empresas = $user->hasRole('super_admin')
            ? collect([['id' => 0, 'name' => 'Todas (soporte)']])
            : $user->empresasAcceso()->where('activo', true)->get(['empresas.id', 'name'])
                ->map(fn (Empresa $e) => ['id' => $e->id, 'name' => $e->name]);

        return [
            'ok' => true,
            'user' => ['id' => $user->id, 'name' => $user->name],
            'empresas' => $empresas->values()->all(),
        ];
    }

    public function ligar(User $user, string $chatId): void
    {
        User::where('telegram_chat_id', $chatId)->where('id', '!=', $user->id)->update(['telegram_chat_id' => null]);
        $user->forceFill(['telegram_chat_id' => $chatId])->saveQuietly();
    }

    /** "593 97 900 0505" o "+593979000505" → "+593979000505", como lo guarda el panel. */
    public static function normalizar(string $telefono): string
    {
        return '+'.preg_replace('/\D/', '', $telefono);
    }
}
