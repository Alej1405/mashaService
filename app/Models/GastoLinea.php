<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoLinea extends Model
{
    protected $table = 'gasto_lineas';

    protected $fillable = ['gasto_id','tipo_gasto_id','account_plan_id','descripcion','base','porcentaje_iva','iva','deducible','motivo_no_deducible'];

    protected $casts = ['base' => 'decimal:2', 'porcentaje_iva' => 'decimal:2', 'iva' => 'decimal:2', 'deducible' => 'boolean'];

    public function gasto(): BelongsTo { return $this->belongsTo(Gasto::class); }
    public function tipoGasto(): BelongsTo { return $this->belongsTo(TipoGasto::class); }
    public function cuenta(): BelongsTo { return $this->belongsTo(AccountPlan::class, 'account_plan_id'); }
}
