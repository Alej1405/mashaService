<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasEmpresa;

class BankAccount extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bankAccount) {
            if (!$bankAccount->account_plan_id) {
                $code = $bankAccount->tipo_cuenta === 'ahorros'
                    ? '1.1.01.04'
                    : '1.1.01.03';

                $cuenta = \App\Models\AccountPlan::where('empresa_id', $bankAccount->empresa_id)
                    ->where('code', $code)
                    ->first();

                // Fallback a cualquier cuenta bancaria
                if (!$cuenta) {
                    $cuenta = \App\Models\AccountPlan::where('empresa_id', $bankAccount->empresa_id)
                        ->where('code', 'like', '1.1.01%')
                        ->where('type', 'activo')
                        ->first();
                }

                if ($cuenta) {
                    $bankAccount->account_plan_id = $cuenta->id;
                }
            }
        });

        static::updating(function ($bankAccount) {
            if ($bankAccount->isDirty('tipo_cuenta')) {
                $code = $bankAccount->tipo_cuenta === 'ahorros'
                    ? '1.1.01.04'
                    : '1.1.01.03';

                $cuenta = \App\Models\AccountPlan::where('empresa_id', $bankAccount->empresa_id)
                    ->where('code', $code)
                    ->first();

                if ($cuenta) {
                    $bankAccount->account_plan_id = $cuenta->id;
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

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->bank->nombre} - {$this->numero_cuenta}";
    }
}
