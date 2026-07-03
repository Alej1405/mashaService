<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sesión de Telegram ↔ usuario del ERP para la integración n8n.
 * El token vive solo aquí (hasheado); vaciar la tabla corta el acceso de n8n.
 */
class TelegramSession extends Model
{
    protected $fillable = [
        'chat_id',
        'user_id',
        'empresa_id',
        'token_hash',
        'estado',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function estaVigente(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isFuture();
    }

    /** Hash con el que se guarda/busca un token de sesión. */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
