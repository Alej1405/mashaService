<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditCardMovement extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($movement) {
            $card = $movement->creditCard;
            if ($movement->tipo === 'cargo') {
                $card->increment('saldo_utilizado', $movement->monto);
            } elseif ($movement->tipo === 'pago') {
                $card->decrement('saldo_utilizado', $movement->monto);
            }
        });
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
