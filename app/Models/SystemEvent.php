<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemEvent extends Model
{
    protected $fillable = [
        'empresa_id',
        'tipo',
        'modulo',
        'titulo',
        'mensaje',
        'contexto',
        'resuelto',
        'resuelto_at',
        'resuelto_por',
    ];

    protected $casts = [
        'contexto'    => 'array',
        'resuelto'    => 'boolean',
        'resuelto_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }

    public function tipoColor(): string
    {
        return match ($this->tipo) {
            'error'       => 'danger',
            'warning'     => 'warning',
            'info'        => 'info',
            'job_fallido' => 'danger',
            default       => 'gray',
        };
    }

    public function tipoLabel(): string
    {
        return match ($this->tipo) {
            'error'       => 'Error',
            'warning'     => 'Advertencia',
            'info'        => 'Información',
            'job_fallido' => 'Job Fallido',
            default       => $this->tipo,
        };
    }

    public static function registrar(
        string $titulo,
        string $mensaje,
        string $tipo = 'error',
        ?int $empresaId = null,
        ?string $modulo = null,
        array $contexto = []
    ): self {
        return self::create([
            'empresa_id' => $empresaId,
            'tipo'       => $tipo,
            'modulo'     => $modulo,
            'titulo'     => $titulo,
            'mensaje'    => $mensaje,
            'contexto'   => $contexto ?: null,
        ]);
    }

    public function resolver(): void
    {
        $this->update([
            'resuelto'    => true,
            'resuelto_at' => now(),
            'resuelto_por' => auth()->id(),
        ]);
    }
}
