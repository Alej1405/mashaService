<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ítem del menú de un punto de venta (cliente). Tabla independiente
 * `customer_menu_items`. El stock/catálogo de la tienda es otra cosa: esto es solo
 * la carta pública del punto de venta (nombre, detalle, precio).
 */
class CustomerMenuItem extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'customer_id',
        'nombre',
        'descripcion',
        'precio',
        'imagen',
        'orden',
        'activo',
        'es_promocion',
        'precio_promo',
    ];

    protected $casts = [
        'precio'       => 'decimal:2',
        'orden'        => 'integer',
        'activo'       => 'boolean',
        'es_promocion' => 'boolean',
        'precio_promo' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
