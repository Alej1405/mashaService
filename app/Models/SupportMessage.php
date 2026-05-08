<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'chat_id',
        'user_id',
        'remitente',
        'mensaje',
        'leido_at',
    ];

    protected $casts = [
        'leido_at' => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(SupportChat::class, 'chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromAdmin(): bool
    {
        return $this->remitente === 'admin';
    }
}
