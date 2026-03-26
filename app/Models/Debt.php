<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Debt $debt) {
            if (empty($debt->numero)) {
                $year = now()->year;
                $last = static::withoutGlobalScopes()
                    ->where('numero', 'like', "DEU-{$year}-%")
                    ->count();
                $debt->numero = sprintf('DEU-%d-%05d', $year, $last + 1);
            }

            if (empty($debt->saldo_pendiente)) {
                $debt->saldo_pendiente = $debt->monto_original ?? 0;
            }
        });
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function accountPlan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function amortizationLines(): HasMany
    {
        return $this->hasMany(DebtAmortizationLine::class)->orderBy('numero_cuota');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class)->orderBy('fecha_pago');
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'prestamo_bancario'    => 'Préstamo Bancario',
            'tarjeta_credito'      => 'Tarjeta de Crédito',
            'prestamo_personal'    => 'Préstamo Personal',
            'prestamo_empresarial' => 'Préstamo Empresarial',
            default                => 'Otro',
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'borrador'     => 'gray',
            'activa'       => 'info',
            'parcial'      => 'warning',
            'pagada'       => 'success',
            'vencida'      => 'danger',
            'refinanciada' => 'primary',
            default        => 'gray',
        };
    }

    public function getPorcentajePagadoAttribute(): float
    {
        if (!$this->monto_original) return 0;
        $capital = $this->payments()->sum('monto_capital');
        return round(($capital / $this->monto_original) * 100, 1);
    }
}
