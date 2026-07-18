<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parte FINANCIERA del cliente. 1:1 con Customer. Aísla la cuenta contable y los
 * saldos/crédito del núcleo de identidad del cliente.
 */
class CustomerFinance extends Model
{
    use HasEmpresa;

    protected $table = 'customer_finance';

    protected $fillable = [
        'empresa_id',
        'customer_id',
        'cuenta_contable_id',
        'saldo',
        'limite_credito',
    ];

    protected $casts = [
        'saldo'          => 'decimal:2',
        'limite_credito' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class, 'cuenta_contable_id');
    }
}
