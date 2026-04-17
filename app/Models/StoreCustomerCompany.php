<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCustomerCompany extends Model
{
    protected $table = 'store_customer_companies';

    protected $fillable = [
        'store_customer_id',
        'empresa_id',
        'ruc',
        'nombre',
        'direccion',
        'correo',
        'cargo',
    ];

    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
