<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Imagen de la galería de la landing de un punto de venta (cliente). Tabla propia
 * `customer_web_images` para no cargar customer_web: quien no sube imágenes no genera
 * filas. Máximo 5 por cliente (tope validado en el portal).
 */
class CustomerWebImage extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'customer_id',
        'imagen',
        'alt',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
