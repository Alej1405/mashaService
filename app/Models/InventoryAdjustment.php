<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    protected $table = 'inventory_adjustments';

    protected $fillable = [
        'inventory_item_id',
        'empresa_id',
        'item_presentation_id',
        'cantidad_presentacion',
        'factor_empaque',
        'total_unidades_base',
        'tipo',
        'stock_anterior',
        'stock_nuevo',
        'costo_unitario',
        'motivo',
        'journal_entry_id',
        'user_id',
    ];

    protected $casts = [
        'cantidad_presentacion' => 'decimal:6',
        'factor_empaque'        => 'decimal:6',
        'total_unidades_base'   => 'decimal:6',
        'stock_anterior'        => 'decimal:6',
        'stock_nuevo'           => 'decimal:6',
        'costo_unitario'        => 'decimal:6',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function itemPresentation(): BelongsTo
    {
        return $this->belongsTo(ItemPresentation::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
