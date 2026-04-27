<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DebtPayment extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DebtPayment $payment) {
            if (empty($payment->numero)) {
                $year   = now()->year;
                $prefix = "PAD-{$year}-";

                $seq = DB::transaction(function () use ($prefix) {
                    $max = static::withoutGlobalScopes()
                        ->where('numero', 'like', "{$prefix}%")
                        ->lockForUpdate()
                        ->max('numero');
                    return $max ? ((int) substr($max, -5)) + 1 : 1;
                });

                $payment->numero = sprintf('PAD-%d-%05d', $year, $seq);
            }

            $payment->total = ($payment->monto_capital ?? 0)
                + ($payment->monto_interes ?? 0)
                + ($payment->monto_mora ?? 0);
        });
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function amortizationLine(): BelongsTo
    {
        return $this->belongsTo(DebtAmortizationLine::class, 'debt_amortization_line_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
