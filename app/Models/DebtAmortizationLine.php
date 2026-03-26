<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtAmortizationLine extends Model
{
    protected $guarded = [];

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(DebtPayment::class, 'debt_payment_id');
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'warning',
            'pagada'    => 'success',
            'vencida'   => 'danger',
            default     => 'gray',
        };
    }
}
