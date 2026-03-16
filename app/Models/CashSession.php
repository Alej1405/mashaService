<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function scopeAbierta($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function calcularDiferencia()
    {
        if ($this->estado === 'cerrada' && $this->saldo_cierre !== null) {
            $esperado = $this->saldo_apertura + $this->total_ingresos - $this->total_egresos;
            $this->diferencia = $this->saldo_cierre - $esperado;
            $this->save();
        }
    }
}
