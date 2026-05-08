<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support-chat.' . $this->message->chat_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'chat_id'    => $this->message->chat_id,
            'remitente'  => $this->message->remitente,
            'mensaje'    => $this->message->mensaje,
            'user_name'  => $this->message->user?->name ?? 'Soporte',
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
