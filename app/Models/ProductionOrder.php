<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEmpresa;
use App\Models\ProductPresentation;
use App\Models\InventoryItem;
use Illuminate\Support\Str;

class ProductionOrder extends Model
{
    use HasEmpresa;

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'cantidad_producida' => 'decimal:4',
        'costo_total' => 'decimal:4',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->referencia)) {
                $year = now()->year;
                $last = self::withoutGlobalScopes()
                    ->where('empresa_id', $model->empresa_id)
                    ->where('referencia', 'like', "PROD-{$year}-%")
                    ->latest('id')
                    ->first();

                $next = $last ? ((int) substr($last->referencia, -5)) + 1 : 1;
                $model->referencia = "PROD-{$year}-" . str_pad($next, 5, '0', STR_PAD_LEFT);
            }

            // Auto-crear/vincular InventoryItem (producto terminado) desde el diseño
            if (empty($model->inventory_item_id) && $model->product_presentation_id) {
                $presentation = ProductPresentation::with('productDesign')->find($model->product_presentation_id);
                $design = $presentation?->productDesign;
                if ($design) {
                    $item = InventoryItem::withoutGlobalScopes()
                        ->firstOrCreate(
                            [
                                'empresa_id' => $model->empresa_id,
                                'nombre'     => $design->nombre,
                                'type'       => 'producto_terminado',
                            ],
                            [
                                'codigo'       => 'PT-' . strtoupper(Str::limit(Str::slug($design->nombre), 8, '')),
                                'activo'       => true,
                                'stock_actual' => 0,
                                'stock_minimo' => 0,
                            ]
                        );
                    $model->inventory_item_id = $item->id;

                    // Guardar referencia en el diseño para futuras órdenes
                    if (!$design->inventory_item_id) {
                        $design->updateQuietly(['inventory_item_id' => $item->id]);
                    }
                }
            }
        });
    }

    public function finishedProduct(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function productPresentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionMaterial::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completado_por');
    }

    // Scopes de estado
    public function scopeBorrador($query)
    {
        return $query->where('estado', 'borrador');
    }

    public function scopeCompletado($query)
    {
        return $query->where('estado', 'completado');
    }
}
