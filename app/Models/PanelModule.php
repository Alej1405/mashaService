<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asociación panel ↔ módulo. Cada fila = un módulo visible en un panel.
 * module_key referencia una clave del catálogo config('erp_features').
 */
class PanelModule extends Model
{
    protected $table = 'panel_modules';

    protected $fillable = ['panel_id', 'module_key'];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }
}
