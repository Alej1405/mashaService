<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * El certificado de firma electrónica de la empresa.
 *
 * El archivo vive en disco privado y la clave, cifrada con la APP_KEY: en
 * claro no se guarda ni en la base ni en el log. Caduca cada dos años y sin
 * él no se firma ni un comprobante, así que el panel avisa antes.
 */
class FirmaElectronica extends Model
{
    protected $table = 'firmas_electronicas';

    protected $fillable = [
        'empresa_id', 'archivo', 'clave', 'titular', 'identificacion', 'emisor',
        'numero_serie', 'valido_desde', 'valido_hasta', 'activa', 'cargada_por',
    ];

    protected $casts = [
        'valido_desde' => 'date',
        'valido_hasta' => 'date',
        'activa'       => 'boolean',
    ];

    protected $hidden = ['clave'];

    /** Días antes de la caducidad en que el panel empieza a insistir. */
    public const AVISO_DIAS = 45;

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function setClaveAttribute(?string $valor): void
    {
        $this->attributes['clave'] = $valor ? Crypt::encryptString($valor) : null;
    }

    /** Solo la usa quien va a firmar; nunca se muestra en pantalla. */
    public function claveEnClaro(): ?string
    {
        return $this->attributes['clave'] ? Crypt::decryptString($this->attributes['clave']) : null;
    }

    public function getDiasRestantesAttribute(): ?int
    {
        return $this->valido_hasta
            ? (int) now()->startOfDay()->diffInDays($this->valido_hasta, false)
            : null;
    }

    /**
     * @return array{tono: string, texto: string}
     */
    public function getEstadoAttribute(): array
    {
        $dias = $this->dias_restantes;

        return match (true) {
            $dias === null      => ['tono' => 'n',  'texto' => 'sin fecha de caducidad'],
            $dias < 0           => ['tono' => 'da', 'texto' => 'caducada hace ' . abs($dias) . ' días'],
            $dias === 0         => ['tono' => 'da', 'texto' => 'caduca hoy'],
            $dias <= self::AVISO_DIAS => ['tono' => 'wa', 'texto' => "caduca en {$dias} días"],
            default             => ['tono' => 'ok', 'texto' => "vigente, {$dias} días"],
        };
    }

    public function getVigenteAttribute(): bool
    {
        return $this->activa && ($this->dias_restantes ?? -1) >= 0;
    }
}
