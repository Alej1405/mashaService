<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Categoría de gasto: su cuenta, su casillero del 101 y su retención. */
class TipoGasto extends Model
{
    protected $table = 'tipos_gasto';

    protected $fillable = ['empresa_id','nombre','codigo','account_plan_id','casillero_101','concepto_retencion','deducible','activo'];

    protected $casts = ['deducible' => 'boolean', 'activo' => 'boolean'];

    public function cuenta(): BelongsTo { return $this->belongsTo(AccountPlan::class, 'account_plan_id'); }

    /** Los de la empresa más los generales del sistema. */
    public function scopeDisponibles($query, ?int $empresaId)
    {
        return $query->where('activo', true)
            ->where(fn ($q) => $q->where('empresa_id', $empresaId)->orWhereNull('empresa_id'));
    }
}
