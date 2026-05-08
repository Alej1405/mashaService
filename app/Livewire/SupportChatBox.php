<?php

namespace App\Livewire;

use App\Events\SupportMessageSent;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use Livewire\Component;

class SupportChatBox extends Component
{
    public SupportChat $chat;
    public string $newMessage = '';
    public bool $isAdmin = false;

    public function getListeners(): array
    {
        return [
            "echo-private:support-chat.{$this->chat->id},.message.sent" => 'handleIncomingMessage',
        ];
    }

    public function mount(SupportChat $chat, bool $isAdmin = false): void
    {
        $this->chat    = $chat;
        $this->isAdmin = $isAdmin;
        $this->markAsRead();
    }

    public function handleIncomingMessage(): void
    {
        $this->chat->refresh();
        $this->markAsRead();
    }

    public function sendMessage(): void
    {
        $this->validate(['newMessage' => 'required|string|max:2000']);

        $remitente = $this->isAdmin ? 'admin' : 'user';
        $userId    = $this->isAdmin ? null : auth()->id();

        $message = SupportMessage::create([
            'chat_id'   => $this->chat->id,
            'user_id'   => $userId,
            'remitente' => $remitente,
            'mensaje'   => $this->newMessage,
        ]);

        if ($this->chat->status === 'abierto' && $this->isAdmin) {
            $this->chat->update(['status' => 'en_proceso']);
        }

        broadcast(new SupportMessageSent($message));

        $this->newMessage = '';
        $this->chat->refresh();
    }

    public function closeChat(): void
    {
        $this->chat->update(['status' => 'cerrado']);
        $this->chat->refresh();
    }

    public function reopenChat(): void
    {
        $this->chat->update(['status' => 'abierto']);
        $this->chat->refresh();
    }

    private function markAsRead(): void
    {
        $field = $this->isAdmin ? 'admin_last_read_at' : 'user_last_read_at';
        $this->chat->update([$field => now()]);

        $remitente = $this->isAdmin ? 'user' : 'admin';
        SupportMessage::where('chat_id', $this->chat->id)
            ->where('remitente', $remitente)
            ->whereNull('leido_at')
            ->update(['leido_at' => now()]);
    }

    public function render()
    {
        return view('livewire.support-chat-box', [
            'messages' => $this->chat->messages()->get(),
        ]);
    }
}
