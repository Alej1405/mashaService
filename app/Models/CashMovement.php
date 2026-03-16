<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($movement) {
            // Actualizar saldo en Caja
            $cashRegister = $movement->cashRegister;
            if ($movement->tipo === 'ingreso') {
                $cashRegister->increment('saldo_actual', $movement->monto);
            } else {
                $cashRegister->decrement('saldo_actual', $movement->monto);
            }

            // Actualizar totales en Sesión
            if ($movement->cash_session_id) {
                $session = $movement->cashSession;
                if ($movement->tipo === 'ingreso') {
                    $session->increment('total_ingresos', $movement->monto);
                } else {
                    $session->increment('total_egresos', $movement->monto);
                }
            }
        });
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
