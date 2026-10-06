<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Espera respuesta de soporte: está abierto (nadie de soporte lo tocó) o el
     * último mensaje del hilo es de la empresa. Usa `ultimo_remitente` si la
     * consulta lo trajo (scopeConUltimoRemitente) para no consultar por fila.
     */
    public function esperandoSoporte(): bool
    {
        if ($this->status === self::CERRADO) {
            return false;
        }
        if ($this->status === self::ABIERTO) {
            return true;
        }

        $ultimo = $this->ultimo_remitente ?? static::ultimoRemitente()->where('support_ticket_id', $this->id)->value('remitente');

        return $ultimo === SupportTicketMensaje::EMPRESA;
    }

    /** Agrega `ultimo_remitente` a cada ticket de la consulta. */
    public function scopeConUltimoRemitente(Builder $query): Builder
    {
        return $query->addSelect(['ultimo_remitente' => static::ultimoRemitente()->whereColumn('support_ticket_id', 'support_tickets.id')]);
    }

    /** Solo los tickets que esperan respuesta de soporte (misma regla que esperandoSoporte). */
    public function scopeEsperandoSoporte(Builder $query): Builder
    {
        return $query->where('status', '!=', self::CERRADO)->where(fn (Builder $q) => $q
            ->where('status', self::ABIERTO)
            ->orWhere(static::ultimoRemitente()->whereColumn('support_ticket_id', 'support_tickets.id'), SupportTicketMensaje::EMPRESA));
    }

    /** Lo que espera respuesta va primero, luego lo demás en curso, al final lo cerrado. */
    public function scopeOrdenPorAtencion(Builder $query): Builder
    {
        $ultimo = static::ultimoRemitente()->whereColumn('support_ticket_id', 'support_tickets.id');

        return $query->orderByRaw(
            'CASE WHEN status = ? THEN 2 WHEN status = ? OR ('.$ultimo->toSql().') = ? THEN 0 ELSE 1 END',
            [self::CERRADO, self::ABIERTO, SupportTicketMensaje::EMPRESA],
        );
    }

    /** Remitente del mensaje más reciente del hilo (sin filtrar ticket). */
    private static function ultimoRemitente(): Builder
    {
        return SupportTicketMensaje::query()->select('remitente')->orderByDesc('created_at')->orderByDesc('id')->limit(1);
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
