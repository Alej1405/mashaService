<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Un mensaje del hilo de un ticket: texto y/o un adjunto. */
class SupportTicketMensaje extends Model
{
    protected $table = 'support_ticket_mensajes';

    public const SOPORTE = 'soporte';
    public const EMPRESA = 'empresa';
    public const PANEL = 'panel';
    public const TELEGRAM = 'telegram';

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

    public function esDeSoporte(): bool
    {
        return $this->remitente === self::SOPORTE;
    }

    /** "Soporte · Ana · 05/10/2026 14:30 · Telegram" */
    public function encabezado(): string
    {
        $partes = [
            $this->esDeSoporte() ? 'Soporte' : 'Empresa',
            $this->user?->name ?? '—',
            $this->created_at?->format('d/m/Y H:i'),
        ];

        if ($this->canal === self::TELEGRAM) {
            $partes[] = 'Telegram';
        }

        return implode(' · ', array_filter($partes));
    }

    /** El adjunto como lo consumen n8n y la API: null si el mensaje no trae archivo. */
    public function adjuntoPayload(): ?array
    {
        if (! $this->adjunto_path) {
            return null;
        }

        return [
            'url'       => $this->adjuntoUrl(),
            'nombre'    => $this->adjunto_nombre,
            'es_imagen' => $this->esImagen(),
        ];
    }
}
