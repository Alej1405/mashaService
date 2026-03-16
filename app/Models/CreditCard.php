<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creditCard) {
            if (!$creditCard->account_plan_id) {
                // Buscar cuenta específica 2.1.06
                $cuenta = \App\Models\AccountPlan::where('empresa_id', $creditCard->empresa_id)
                    ->where('code', '2.1.06')
                    ->first();

                // Fallback: cualquier pasivo corriente 2.1
                if (!$cuenta) {
                    $cuenta = \App\Models\AccountPlan::where('empresa_id', $creditCard->empresa_id)
                        ->where('code', 'like', '2.1%')
                        ->where('type', 'pasivo')
                        ->whereNotNull('accepts_movements')
                        ->where('accepts_movements', true)
                        ->first();
                }

                // Fallback final: cualquier pasivo
                if (!$cuenta) {
                    $cuenta = \App\Models\AccountPlan::where('empresa_id', $creditCard->empresa_id)
                        ->where('type', 'pasivo')
                        ->where('accepts_movements', true)
                        ->first();
                }

                if ($cuenta) {
                    $creditCard->account_plan_id = $cuenta->id;
                }
            }
        });

        static::updating(function ($creditCard) {
            if ($creditCard->isDirty('empresa_id') 
                || !$creditCard->account_plan_id) {
                
                $cuenta = \App\Models\AccountPlan::where('empresa_id', $creditCard->empresa_id)
                    ->where('code', '2.1.06')
                    ->first();

                if ($cuenta) {
                    $creditCard->account_plan_id = $cuenta->id;
                }
            }
        });
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function accountPlan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class);
    }

    public function creditCardMovements(): HasMany
    {
        return $this->hasMany(CreditCardMovement::class);
    }

    public function getSaldoDisponibleAttribute()
    {
        return $this->limite_credito - $this->saldo_utilizado;
    }

    public function getPorcentajeUsoAttribute()
    {
        if ($this->limite_credito <= 0) return 0;
        return ($this->saldo_utilizado / $this->limite_credito) * 100;
    }
}
