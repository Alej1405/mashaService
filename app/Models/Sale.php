<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEmpresa;

class Sale extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'referencia',
        'fecha',
        'customer_id',
        'tipo_venta',
        'fecha_vencimiento',
        'tipo_operacion',
        'subtotal',
        'iva',
        'total',
        'estado',
        'journal_entry_id',
        'notas',
        'confirmado_por',
        'confirmado_at',
        'factura_electronica_id',
        'clave_acceso',
        'cash_register_id',
        'bank_account_id',
        'credit_card_id',
        'forma_pago',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmado_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->referencia)) {
                $year = now()->year;
                $last = self::where('empresa_id', $model->empresa_id)
                    ->latest('id')
                    ->first();
                
                $next = $last ? ((int) substr($last->referencia, -5)) + 1 : 1;
                $model->referencia = "VEN-{$year}-" . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function confirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }
}
