<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Datos de comercio exterior del cliente (contexto aduana). 1:1 opcional con Customer:
 * solo quien exporta genera fila.
 */
class CustomerExport extends Model
{
    use HasEmpresa;

    protected $table = 'customer_export';

    protected $fillable = [
        'empresa_id',
        'customer_id',
        'es_exportador',
        'pais_destino',
    ];

    protected $casts = [
        'es_exportador' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
