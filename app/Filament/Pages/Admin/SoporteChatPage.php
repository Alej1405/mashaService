<?php

namespace App\Filament\Pages\Admin;

use App\Models\SupportChat;
use Filament\Pages\Page;

class SoporteChatPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Chat de Soporte';
    protected static ?string $navigationGroup = 'Soporte';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.admin.soporte-chat';

    public ?int $activeChatId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function selectChat(int $chatId): void
    {
        $this->activeChatId = $chatId;
    }

    public function getChats()
    {
        return SupportChat::with(['empresa', 'user', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByRaw("FIELD(status, 'abierto', 'en_proceso', 'cerrado')")
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function getActiveChat(): ?SupportChat
    {
        if (! $this->activeChatId) {
            return null;
        }

        return SupportChat::with(['empresa', 'user'])->find($this->activeChatId);
    }

    public function getUnreadCount(): int
    {
        return SupportChat::whereHas('messages', fn($q) => $q->where('remitente', 'user')->whereNull('leido_at'))
            ->count();
    }
}
