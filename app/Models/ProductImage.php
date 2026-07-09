<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Imagen de un producto (store_products). Tabla normalizada: una fila por imagen.
 * es_principal marca la portada; el resto es galería ordenada por `orden`.
 */
class ProductImage extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'store_product_id',
        'path',
        'es_principal',
        'orden',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'orden'        => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'store_product_id');
    }
}
