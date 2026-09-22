<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasEmpresa;

class InventoryMovement extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'inventory_item_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_price',
        'total',
        'date',
        'description',
        'notes',
        'journal_entry_id',
        // Kardex valorado
        'motivo',
        'almacen_id',
        'ubicacion_almacen_id',
        'saldo_cantidad',
        'costo_promedio',
        'saldo_valor',
        'lote',
        'acta_numero',
        'acta_fecha',
        'acta_archivo',
        'user_id',
    ];

    protected $casts = [
        'date'           => 'date',
        'acta_fecha'     => 'date',
        'quantity'       => 'decimal:4',
        'unit_price'     => 'decimal:4',
        'total'          => 'decimal:4',
        'saldo_cantidad' => 'decimal:4',
        'costo_promedio' => 'decimal:4',
        'saldo_valor'    => 'decimal:4',
    ];

    /** Movimientos que no mueven valor, solo ubicación: no generan asiento. */
    public function esTraslado(): bool
    {
        return $this->motivo === 'traslado';
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_almacen_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
