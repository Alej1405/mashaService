<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una jornada de trabajo sobre un pedido: inicio, fin, personas y su mano de obra. */
class ProduccionJornada extends Model
{
    use HasEmpresa;

    protected $table = 'produccion_jornadas';

    protected $fillable = [
        'empresa_id', 'store_order_id', 'numero', 'inicio', 'fin', 'motivo_cierre', 'personas',
        'tarifa_hora', 'horas', 'costo_mano_obra', 'iniciada_por', 'cerrada_por',
    ];

    protected $casts = [
        'inicio'          => 'datetime',
        'fin'             => 'datetime',
        'tarifa_hora'     => 'decimal:2',
        'horas'           => 'decimal:2',
        'costo_mano_obra' => 'decimal:2',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }

    public function iniciadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iniciada_por');
    }

    public function cerradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }
}
