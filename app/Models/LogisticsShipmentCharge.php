<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsShipmentCharge extends Model
{
    protected $table = 'logistics_shipment_charges';

    protected $fillable = [
        'shipment_id',
        'empresa_id',
        'descripcion',
        'monto',
        'iva_pct',
    ];

    protected $casts = [
        'monto'   => 'decimal:2',
        'iva_pct' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Hereda empresa_id del embarque al crear
        static::creating(function (self $charge) {
            if (! $charge->empresa_id && $charge->shipment_id) {
                $charge->empresa_id = LogisticsShipment::find($charge->shipment_id)?->empresa_id;
            }
        });
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(LogisticsShipment::class, 'shipment_id');
    }
}
