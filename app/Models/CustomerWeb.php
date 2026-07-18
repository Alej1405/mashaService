<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parte WEB / landing pública del cliente (punto de venta). 1:1 opcional con Customer:
 * no todos los clientes tienen landing. `publicado` es el toggle: activo = se muestra.
 */
class CustomerWeb extends Model
{
    use HasEmpresa;

    protected $table = 'customer_web';

    protected $fillable = [
        'empresa_id',
        'customer_id',
        'descripcion_web',
        'horario',
        'logo',
        'banner',
        'latitud',
        'longitud',
    ];

    protected $casts = [
        'latitud'  => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
