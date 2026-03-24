<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreCoupon extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'codigo',
        'tipo',
        'valor',
        'minimo_compra',
        'maximo_usos',
        'usos_actuales',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'valor'          => 'decimal:4',
        'minimo_compra'  => 'decimal:4',
        'maximo_usos'    => 'integer',
        'usos_actuales'  => 'integer',
        'activo'         => 'boolean',
        'fecha_inicio'   => 'date',
        'fecha_fin'      => 'date',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }

    public function isValid(float $subtotal = 0): bool
    {
        if (!$this->activo) return false;
        if ($this->minimo_compra && $subtotal < $this->minimo_compra) return false;
        if ($this->maximo_usos && $this->usos_actuales >= $this->maximo_usos) return false;
        if ($this->fecha_inicio && now()->lt($this->fecha_inicio)) return false;
        if ($this->fecha_fin && now()->gt($this->fecha_fin)) return false;
        return true;
    }

    public function calcularDescuento(float $subtotal): float
    {
        return $this->tipo === 'porcentaje'
            ? $subtotal * ($this->valor / 100)
            : min((float) $this->valor, $subtotal);
    }
}
