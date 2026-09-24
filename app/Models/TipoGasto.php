<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Categoría de gasto: su cuenta, su casillero del 101 y su retención. */
class TipoGasto extends Model
{
    protected $table = 'tipos_gasto';

    protected $fillable = [
        'codigo_cuenta','empresa_id','nombre','codigo','account_plan_id','casillero_101','concepto_retencion','deducible','activo'];

    protected $casts = ['deducible' => 'boolean', 'activo' => 'boolean'];

    public function cuenta(): BelongsTo { return $this->belongsTo(AccountPlan::class, 'account_plan_id'); }

    /** Los de la empresa más los generales del sistema. */
    public function scopeDisponibles($query, ?int $empresaId)
    {
        return $query->where('activo', true)
            ->where(fn ($q) => $q->where('empresa_id', $empresaId)->orWhereNull('empresa_id'));
    }

    /**
     * La cuenta contable de este tipo en el plan de una empresa.
     *
     * Los tipos son globales y las cuentas de cada empresa: el puente es el
     * código del plan estándar, que es igual en todas.
     */
    public function cuentaEn(int $empresaId): ?int
    {
        if ($this->account_plan_id) {
            return $this->account_plan_id;
        }

        if (! $this->codigo_cuenta) {
            return null;
        }

        return \App\Models\AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('code', $this->codigo_cuenta)
            ->value('id');
    }
}
