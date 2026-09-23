<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Porcentaje de retención vigente por fecha.
 *
 * Un comprobante se retiene con el porcentaje que regía el día de su fecha, no
 * con el de hoy: la tabla de renta cambió el 1 de marzo de 2026.
 */
class PorcentajeRetencion extends Model
{
    protected $table = 'porcentajes_retencion';

    protected $fillable = ['empresa_id','tipo','concepto','codigo_sri','porcentaje','vigente_desde','vigente_hasta','resolucion'];

    protected $casts = ['vigente_desde' => 'date', 'vigente_hasta' => 'date', 'porcentaje' => 'decimal:2'];

    public function scopeVigentesEn($query, string|Carbon $fecha)
    {
        $dia = Carbon::parse($fecha)->toDateString();

        return $query->whereDate('vigente_desde', '<=', $dia)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $dia));
    }

    public static function para(string $tipo, string $concepto, string|Carbon $fecha, ?int $empresaId = null): ?self
    {
        return static::query()
            ->where(fn ($q) => $q->where('empresa_id', $empresaId)->orWhereNull('empresa_id'))
            ->where('tipo', $tipo)
            ->where('concepto', $concepto)
            ->vigentesEn($fecha)
            ->orderByDesc('empresa_id')
            ->orderByDesc('vigente_desde')
            ->first();
    }
}
