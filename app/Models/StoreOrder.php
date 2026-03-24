<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreOrder extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'store_customer_id',
        'numero',
        'estado',
        'subtotal',
        'descuento',
        'total',
        'store_coupon_id',
        'metodo_pago',
        'estado_pago',
        'referencia_pago',
        'direccion_envio',
        'notas_cliente',
        'sale_id',
    ];

    protected $casts = [
        'subtotal'       => 'decimal:4',
        'descuento'      => 'decimal:4',
        'total'          => 'decimal:4',
        'direccion_envio' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->numero)) {
                $year = now()->year;
                $last = self::withoutGlobalScopes()
                    ->where('empresa_id', $model->empresa_id)
                    ->latest('id')->first();
                $next        = $last ? ((int) substr($last->numero, -5)) + 1 : 1;
                $model->numero = 'ECO-' . $year . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'store_customer_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(StoreOrderItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(StoreCoupon::class, 'store_coupon_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
