<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Tarifa de IVA vigente a una fecha. Nunca se lee del código. */
class TarifaIva extends Model
{
    protected $table = 'tarifas_iva';

    protected $fillable = ['empresa_id','codigo','porcentaje','descripcion','vigente_desde','vigente_hasta'];

    protected $casts = ['vigente_desde' => 'date', 'vigente_hasta' => 'date', 'porcentaje' => 'decimal:2'];

    /** La tarifa general que regía en esa fecha, no la de hoy. */
    public static function generalEn(string|Carbon $fecha, ?int $empresaId = null): ?self
    {
        $dia = Carbon::parse($fecha)->toDateString();

        return static::query()
            ->where(fn ($q) => $q->where('empresa_id', $empresaId)->orWhereNull('empresa_id'))
            ->whereIn('codigo', ['4', '2'])
            ->whereDate('vigente_desde', '<=', $dia)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $dia))
            ->orderByDesc('empresa_id')
            ->orderByDesc('vigente_desde')
            ->first();
    }

    public function getVigenteAttribute(): bool
    {
        return $this->vigente_hasta === null || $this->vigente_hasta->isFuture();
    }
}
