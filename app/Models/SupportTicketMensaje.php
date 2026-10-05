<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Un mensaje del hilo de un ticket: texto y/o un adjunto. */
class SupportTicketMensaje extends Model
{
    protected $table = 'support_ticket_mensajes';

    protected $fillable = [
        'support_ticket_id', 'user_id', 'remitente', 'canal',
        'mensaje', 'adjunto_path', 'adjunto_nombre', 'adjunto_mime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adjuntoUrl(): ?string
    {
        return $this->adjunto_path ? Storage::disk('public')->url($this->adjunto_path) : null;
    }

    public function esImagen(): bool
    {
        return str_starts_with((string) $this->adjunto_mime, 'image/');
    }
}
