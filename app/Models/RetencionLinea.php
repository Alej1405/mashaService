<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetencionLinea extends Model
{
    protected $table = 'retencion_lineas';

    protected $fillable = ['retencion_id','porcentaje_retencion_id','tipo','concepto','codigo_sri','base','porcentaje','valor'];

    protected $casts = ['base' => 'decimal:2', 'porcentaje' => 'decimal:2', 'valor' => 'decimal:2'];

    public function retencion(): BelongsTo { return $this->belongsTo(Retencion::class); }
}
