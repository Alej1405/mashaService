<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    use HasEmpresa;

    protected $table = 'support_tickets';

    protected $fillable = [
        'empresa_id', 'user_id', 'asunto', 'descripcion', 'prioridad', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prioridadLabel(): string
    {
        return match ($this->prioridad) {
            'alta'  => 'Alta',
            'media' => 'Media',
            'baja'  => 'Baja',
            default => ucfirst($this->prioridad),
        };
    }

    public function prioridadColor(): string
    {
        return match ($this->prioridad) {
            'alta'  => 'danger',
            'media' => 'warning',
            'baja'  => 'gray',
            default => 'gray',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'abierto'    => 'Abierto',
            'en_proceso' => 'En proceso',
            'cerrado'    => 'Cerrado',
            default      => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'abierto'    => 'info',
            'en_proceso' => 'warning',
            'cerrado'    => 'gray',
            default      => 'gray',
        };
    }
}
