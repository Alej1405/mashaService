<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasEmpresa;

    protected $table = 'support_tickets';

    public const ABIERTO = 'abierto';
    public const EN_PROCESO = 'en_proceso';
    public const CERRADO = 'cerrado';
    public const ESTADOS = [self::ABIERTO, self::EN_PROCESO, self::CERRADO];

    /** Tope de cada adjunto en KB: Telegram no deja descargar más de 20 MB. */
    public const MAX_ADJUNTO_KB = 20480;

    protected $fillable = [
        'empresa_id', 'user_id', 'asunto', 'descripcion', 'prioridad', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(SupportTicketMensaje::class);
    }

    /** @return array<string,string> estado => etiqueta, para selectores y filtros */
    public static function opcionesEstado(): array
    {
        return [
            self::ABIERTO    => 'Abierto',
            self::EN_PROCESO => 'En proceso',
            self::CERRADO    => 'Cerrado',
        ];
    }

    /** Un ticket cerrado ya no recibe mensajes, ni desde el panel ni desde Telegram. */
    public function admiteMensajes(): bool
    {
        return $this->status !== self::CERRADO;
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
        return self::opcionesEstado()[$this->status] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::ABIERTO    => 'info',
            self::EN_PROCESO => 'warning',
            self::CERRADO    => 'gray',
            default          => 'gray',
        };
    }
}
