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
        static::creating(function ($item) {
            if (empty($item->codigo)) {
                $item->codigo = 'INV-' . strtoupper(substr(uniqid(), -8));
            }

            // Cada producto nace con su QR: sin token no hay etiqueta que pegar.
            if (empty($item->qr_token)) {
                $item->qr_token = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
            }
        });

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
        'foto_path',
        'ubicacion_almacen_id',
        'type',
        'measurement_unit_id',
        'purchase_unit_id',
        'conversion_factor',
        'account_plan_id',
        'presentation_id',
        'supplier_id',
        'purchase_price',
        'sale_price',
        'qr_token',
        'stock_actual',
        'costo_promedio',
        'saldo_valorado',
        'stock_minimo',
        'lote',
        'fecha_caducidad',
        'activo',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
    ];

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'purchase_unit_id');
    }

    public function accountPlan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function ubicacionAlmacen(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ItemPresentation::class, 'presentation_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(InventoryItemFile::class);
    }

    public function getStockBajoAttribute(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    /** Dónde está repartido el stock de este ítem. */
    public function stocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    /** Filas del kardex, de la más antigua a la más reciente. */
    public function movimientos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryMovement::class)->orderBy('date')->orderBy('id');
    }



    /**
     * Lo que queda grabado en el QR.
     *
     * Es una URL corta y absoluta: la cámara del celular abre URLs sin ayuda de
     * nadie. Un código suelto obligaría a instalar un lector, que es justo la
     * fricción que se quiere evitar en la bodega.
     */
    public function urlQr(): string
    {
        return rtrim(config('app.url'), '/') . '/i/' . $this->qr_token;
    }

    /** QR en SVG, listo para imprimir en la etiqueta de la gaveta. */
    public function qrSvg(int $tamano = 150): string
    {
        return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size($tamano)
            ->margin(1)
            // 'M' tolera hasta un 15% de daño: suficiente para una etiqueta
            // que vive en una bodega y se raya.
            ->errorCorrection('M')
            ->generate($this->urlQr());
    }

}
