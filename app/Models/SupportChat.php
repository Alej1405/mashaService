<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportChat extends Model
{
    protected $fillable = [
        'empresa_id',
        'user_id',
        'status',
        'admin_last_read_at',
        'user_last_read_at',
    ];

    protected $casts = [
        'admin_last_read_at' => 'datetime',
        'user_last_read_at'  => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'chat_id')->orderBy('created_at');
    }

    public function lastMessage(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'chat_id')->latest()->limit(1);
    }

    public function unreadByAdmin(): int
    {
        return $this->messages()
            ->where('remitente', 'user')
            ->where(function ($q) {
                $q->whereNull('leido_at');
            })
            ->count();
    }

    public function unreadByUser(): int
    {
        return $this->messages()
            ->where('remitente', 'admin')
            ->whereNull('leido_at')
            ->count();
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'abierto'    => 'success',
            'en_proceso' => 'warning',
            'cerrado'    => 'gray',
            default      => 'gray',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'abierto'    => 'Abierto',
            'en_proceso' => 'En proceso',
            'cerrado'    => 'Cerrado',
            default      => $this->status,
        };
    }
}
