<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un paso del flujo de un pedido: qué estado, cuándo y quién. */
class StoreOrderHistorial extends Model
{
    use HasEmpresa;

    protected $table = 'store_order_historial';

    protected $fillable = ['empresa_id', 'store_order_id', 'estado', 'ocurrido_en', 'user_id', 'nota'];

    protected $casts = ['ocurrido_en' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
