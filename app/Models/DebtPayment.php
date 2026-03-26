<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DebtPayment $payment) {
            if (empty($payment->numero)) {
                $year = now()->year;
                $last = static::withoutGlobalScopes()
                    ->where('numero', 'like', "PAD-{$year}-%")
                    ->count();
                $payment->numero = sprintf('PAD-%d-%05d', $year, $last + 1);
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
