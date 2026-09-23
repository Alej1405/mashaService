<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gasto que no pasa por inventario.
 *
 * Es lo que sostiene el estado de resultados y el 101: alimentación,
 * transporte, servicios básicos, arriendo, honorarios. Cada línea dice si es
 * deducible, porque el formulario lo pide separado.
 */
class Gasto extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id','supplier_id','journal_entry_id','retencion_id','numero_documento','autorizacion',
        'tipo_documento','fecha','descripcion','forma_pago','subtotal','iva','total','retenido','no_deducible','estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2', 'iva' => 'decimal:2', 'total' => 'decimal:2',
        'retenido' => 'decimal:2', 'no_deducible' => 'decimal:2',
    ];

    public function lineas(): HasMany { return $this->hasMany(GastoLinea::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function retencion(): BelongsTo { return $this->belongsTo(Retencion::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }

    public function getPagadoAttribute(): float
    {
        return round((float) $this->total - (float) $this->retenido, 2);
    }
}
