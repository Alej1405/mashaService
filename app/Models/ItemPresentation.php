<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEmpresa;

class ItemPresentation extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'measurement_unit_id',
        'capacidad',
        'activo',
    ];

    protected $casts = [
        'capacidad' => 'decimal:4',
        'activo'    => 'boolean',
    ];

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'presentation_id');
    }
}
