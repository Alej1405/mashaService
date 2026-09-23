<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Una declaración pedida. Nace pendiente y vive hasta que el archivo existe.
 */
class Declaracion extends Model
{
    protected $table = 'declaraciones';

    protected $fillable = [
        'empresa_id', 'seguimiento', 'tipo', 'anio', 'mes', 'estado',
        'datos', 'avisos', 'archivo', 'mensaje', 'solicitado_por', 'generado_en',
    ];

    protected $casts = [
        'datos'       => 'array',
        'avisos'      => 'array',
        'generado_en' => 'datetime',
    ];

    public const TIPOS = [
        'f104' => 'Formulario 104 · IVA',
        'ats'  => 'Anexo transaccional',
        'f103' => 'Formulario 103 · retenciones',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $d) {
            $d->seguimiento ??= (string) Str::uuid();
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    /** El periodo como lo nombra el SRI: 09/2026. */
    public function getPeriodoAttribute(): string
    {
        return $this->mes ? sprintf('%02d/%d', $this->mes, $this->anio) : (string) $this->anio;
    }

    /** Un casillero de una declaración ya calculada. */
    public function casillero(string $codigo): float
    {
        return (float) ($this->datos['casilleros'][$codigo] ?? 0);
    }

    public function scopeListas($q)
    {
        return $q->where('estado', 'listo');
    }
}
