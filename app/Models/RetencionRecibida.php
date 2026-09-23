<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Retención que un cliente nos practicó.
 *
 * No es gasto: es impuesto pagado por anticipado. En el 101 se resta del
 * impuesto causado, y por eso sin registrarlas se paga de más.
 */
class RetencionRecibida extends Model
{
    use HasEmpresa;

    protected $table = 'retenciones_recibidas';

    protected $fillable = ['empresa_id','customer_id','sale_id','journal_entry_id','numero','fecha','tipo','concepto','codigo_sri','base','porcentaje','valor'];

    protected $casts = ['fecha' => 'date', 'base' => 'decimal:2', 'porcentaje' => 'decimal:2', 'valor' => 'decimal:2'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
}
