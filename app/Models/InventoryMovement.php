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
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
