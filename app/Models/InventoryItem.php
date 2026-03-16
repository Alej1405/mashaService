<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEmpresa;

class InventoryItem extends Model
{
    use HasEmpresa;

    protected static function booted()
    {
        static::saving(function ($item) {
            // Asigna automáticamente el plan de cuentas (como Inventario) basado en su tipo, 
            // buscando el movimiento de compra al contado (que generalmente es su cuenta de Activo)
            if ($item->type && !$item->account_plan_id) {
                try {
                    $empresaId = $item->empresa_id ?? filament()->getTenant()->id ?? null;
                    $cuenta = \App\Services\AccountingService::getMapeo($empresaId, $item->type, 'compra_contado');
                    $item->account_plan_id = $cuenta->id;
                } catch (\Exception $e) {
                    // Si no lo encuentra, lo deja en null
                }
            }
        });
    }

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'descripcion',
        'type',
        'measurement_unit_id',
        'account_plan_id',
        'supplier_id',
        'purchase_price',
        'sale_price',
        'stock_actual',
        'stock_minimo',
        'lote',
        'fecha_caducidad',
        'activo',
    ];

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function accountPlan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(InventoryItemFile::class);
    }

    public function getStockBajoAttribute(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }
}
