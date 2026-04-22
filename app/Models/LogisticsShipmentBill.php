<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsShipmentBill extends Model
{
    use HasEmpresa;

    protected $table = 'logistics_shipment_bills';

    protected $fillable = [
        'empresa_id',
        'shipment_id',
        'supplier_id',
        'descripcion',
        'numero_factura_proveedor',
        'fecha_factura',
        'subtotal',
        'iva_pct',
        'iva_monto',
        'total',
        'estado',
        'fecha_pago',
        'factura_pdf_path',
        'notas',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'iva_monto'     => 'decimal:2',
        'total'         => 'decimal:2',
        'fecha_factura' => 'date',
        'fecha_pago'    => 'date',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(LogisticsShipment::class, 'shipment_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
