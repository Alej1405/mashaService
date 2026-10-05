<?php

namespace App\Modules\Soporte\Queries;

use App\Models\Empresa;
use App\Models\TelegramSession;
use App\Models\User;
use App\Shared\Attributes\Documentado;

/**
 * Chats de Telegram a los que llega un aviso de soporte: el chat ligado al
 * usuario (por su teléfono o al iniciar sesión) y, por compatibilidad, el de
 * sus sesiones del bot. Avisar no requiere sesión vigente; responder sí.
 */
#[Documentado(
    grupo: 'Soporte',
    descripcion: 'Devuelve los chat_id de Telegram del equipo de soporte o de los usuarios de una empresa.',
    tipo: 'query',
)]
final class DestinatariosTelegram
{
    /** @return array<int,string> */
    public function soporte(): array
    {
        return $this->chats(User::role('super_admin')->pluck('id')->all());
    }

    /** @return array<int,string> */
    public function empresa(int $empresaId): array
    {
        $empresa = Empresa::find($empresaId);
        if (! $empresa) {
            return [];
        }

        $ids = $empresa->users()->pluck('users.id')
            ->merge($empresa->usuariosAcceso()->pluck('users.id'))
            ->unique()->all();

        return $this->chats($ids);
    }

    /** @return array<int,string> */
    public function delUsuario(int $userId): array
    {
        return $this->chats([$userId]);
    }

    /** @param array<int,int> $userIds @return array<int,string> */
    private function chats(array $userIds): array
    {
        return User::whereIn('id', $userIds)->whereNotNull('telegram_chat_id')->pluck('telegram_chat_id')
            ->merge(TelegramSession::whereIn('user_id', $userIds)->pluck('chat_id'))
            ->map(fn ($c) => (string) $c)->unique()->values()->all();
    }
}
