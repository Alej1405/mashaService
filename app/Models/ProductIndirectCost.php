<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductIndirectCost extends Model
{
    protected $fillable = [
        'product_design_id',
        'tipo',
        'descripcion',
        'monto_mensual',
        'frecuencia',
    ];

    protected $casts = [
        'monto_mensual' => 'decimal:2',
    ];

    public function productDesign(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class);
    }
}
