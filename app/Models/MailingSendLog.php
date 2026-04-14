<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MailingSendLog extends Model
{
    public $timestamps = false;

    protected $table = 'mailing_send_log';

    protected $fillable = [
        'empresa_id',
        'email',
        'tipo',
        'referencia_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // ── Tipos ────────────────────────────────────────────────────────────────
    public const TIPO_CARTA    = 'carta_presentacion';
    public const TIPO_NOTICIA  = 'noticia';
    public const TIPO_CAMPANA  = 'campana';

    /**
     * Comprueba si este email ya recibió un correo de este tipo recientemente.
     *
     * Reglas:
     *  - carta_presentacion : no re-enviar si ya se envió en los últimos 7 días
     *  - noticia / campana  : no re-enviar si ya se envió el mismo referencia_id (nunca)
     *
     * Devuelve true si SE DEBE OMITIR el envío.
     */
    public static function yaEnviado(
        int     $empresaId,
        string  $email,
        string  $tipo,
        ?int    $referenciaId = null,
    ): bool {
        $query = static::where('empresa_id', $empresaId)
            ->where('email',    $email)
            ->where('tipo',     $tipo);

        if ($tipo === self::TIPO_CARTA) {
            // Bloquear si ya se envió en los últimos 7 días
            return $query->where('sent_at', '>=', now()->subDays(7))->exists();
        }

        // Para noticia y campaña: bloquear si ya se envió este referencia_id
        if ($referenciaId !== null) {
            $query->where('referencia_id', $referenciaId);
        }

        return $query->exists();
    }

    /**
     * Registra un envío exitoso en el log.
     */
    public static function registrar(
        int    $empresaId,
        string $email,
        string $tipo,
        ?int   $referenciaId = null,
    ): void {
        static::create([
            'empresa_id'    => $empresaId,
            'email'         => strtolower(trim($email)),
            'tipo'          => $tipo,
            'referencia_id' => $referenciaId,
            'sent_at'       => now(),
        ]);
    }
}
