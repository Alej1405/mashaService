<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Comprobante de retención emitido al proveedor. */
class Retencion extends Model
{
    use HasEmpresa;

    protected $table = 'retenciones';

    protected $fillable = [
        'empresa_id','purchase_id','supplier_id','journal_entry_id','numero','fecha',
        'base_renta','retenido_renta','base_iva','retenido_iva','total_retenido','estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'base_renta' => 'decimal:2', 'retenido_renta' => 'decimal:2',
        'base_iva' => 'decimal:2', 'retenido_iva' => 'decimal:2', 'total_retenido' => 'decimal:2',
    ];

    public function lineas(): HasMany { return $this->hasMany(RetencionLinea::class); }
    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
