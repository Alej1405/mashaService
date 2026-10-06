<?php

namespace App\Modules\Soporte\Queries;

use App\Models\Empresa;
use App\Models\TelegramSession;
use App\Models\User;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Collection;

/**
 * A quién le llega un aviso de soporte: al equipo de soporte (super_admin) o a
 * los usuarios de una empresa. Todos lo ven en la campana del ERP; los que
 * ligaron Telegram, también en el bot.
 */
#[Documentado(
    grupo: 'Soporte',
    descripcion: 'Devuelve los usuarios que reciben un aviso de soporte y sus chats de Telegram.',
    tipo: 'query',
)]
final class DestinatariosSoporte
{
    /** @return Collection<int,User> */
    public function soporte(): Collection
    {
        return User::role('super_admin')->get();
    }

    /** @return Collection<int,User> */
    public function empresa(int $empresaId): Collection
    {
        $empresa = Empresa::find($empresaId);
        if (! $empresa) {
            return collect();
        }

        return $empresa->users()->get()
            ->merge($empresa->usuariosAcceso()->get())
            ->unique('id')
            ->values();
    }

    /**
     * Chats de Telegram de esas personas: el ligado al usuario y, por compatibilidad,
     * el de sus sesiones del bot. Avisar no requiere sesión vigente.
     *
     * @param Collection<int,User> $usuarios
     * @return array<int,string>
     */
    public function chats(Collection $usuarios): array
    {
        $ids = $usuarios->pluck('id')->all();

        return $usuarios->pluck('telegram_chat_id')->filter()
            ->merge(TelegramSession::whereIn('user_id', $ids)->pluck('chat_id'))
            ->map(fn ($chat) => (string) $chat)
            ->unique()->values()->all();
    }
}
