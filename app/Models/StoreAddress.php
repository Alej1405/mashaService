<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreAddress extends Model
{
    protected $fillable = [
        'store_customer_id',
        'nombre_destinatario',
        'linea1',
        'linea2',
        'ciudad',
        'provincia',
        'pais',
        'codigo_postal',
        'telefono',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'store_customer_id');
    }
}
