<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cashRegister) {
            if (!$cashRegister->account_plan_id) {
                $code = $cashRegister->tipo === 'chica' ? '1.1.01.02' : '1.1.01.01';
                
                $cuenta = AccountPlan::where('empresa_id', $cashRegister->empresa_id)
                    ->where('code', $code)
                    ->first();

                if (!$cuenta) {
                    $cuenta = AccountPlan::where('empresa_id', $cashRegister->empresa_id)
                        ->where('code', 'LIKE', '1.1.01%')
                        ->first();
                }

                if ($cuenta) {
                    $cashRegister->account_plan_id = $cuenta->id;
                }
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function accountPlan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function getSaldoDisponibleAttribute()
    {
        return $this->saldo_actual;
    }
}
