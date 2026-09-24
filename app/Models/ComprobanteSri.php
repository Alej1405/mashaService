<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un comprobante bajado del portal del SRI.
 *
 * Existe para los meses en que el movimiento no está en el ERP: la empresa
 * entra al portal, baja su listado y lo sube aquí. Lo que ya está registrado
 * en el sistema se concilia por clave de acceso para no contarlo dos veces.
 */
class ComprobanteSri extends Model
{
    protected $table = 'comprobantes_sri';

    protected $fillable = [
        'empresa_id', 'clave_acceso', 'origen', 'tipo_comprobante', 'identificacion',
        'razon_social', 'fecha_emision', 'numero', 'establecimiento', 'punto_emision',
        'secuencial', 'base_gravada', 'base_cero', 'iva', 'total', 'desglose',
        'conciliado_con', 'conciliado_id', 'importado_en',
        // Qué fue esa factura, que el portal no lo dice
        'destino', 'tipo_gasto_id', 'inventory_item_id', 'cantidad',
        'clasificado_en', 'clasificado_por',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'importado_en'   => 'datetime',
        'clasificado_en' => 'datetime',
        'cantidad'       => 'decimal:4',
        'base_gravada'  => 'decimal:2',
        'base_cero'     => 'decimal:2',
        'iva'           => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeDelPeriodo($q, int $anio, int $mes)
    {
        return $q->whereYear('fecha_emision', $anio)->whereMonth('fecha_emision', $mes);
    }

    /** Los que no casan con ningún documento del ERP: esos son los que suman. */
    public function scopeSinConciliar($q)
    {
        return $q->whereNull('conciliado_con');
    }

    /**
     * Los que están esperando que alguien diga qué fueron.
     *
     * Suman al 104 y no existen en la contabilidad: mientras queden así, el
     * mes no cuadra y no se puede cerrar.
     */
    public function scopeSinClasificar($q)
    {
        return $q->whereNull('conciliado_con')->whereNull('destino');
    }

    public function tipoGasto(): BelongsTo
    {
        return $this->belongsTo(TipoGasto::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /** La base sobre la que se registra: gravada más la de tarifa cero. */
    public function getBaseAttribute(): float
    {
        return round((float) $this->base_gravada + (float) $this->base_cero, 2);
    }
}
