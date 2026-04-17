<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsPaymentClaim extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'store_customer_id',
        'package_ids',
        'monto_declarado',
        'comprobante_path',
        'notas_cliente',
        'estado',
        'notas_verificador',
        'journal_entry_id',
        'sale_id',
        'verificado_por',
        'verificado_at',
    ];

    protected $casts = [
        'package_ids'    => 'array',
        'monto_declarado' => 'decimal:2',
        'verificado_at'  => 'datetime',
    ];

    public const ESTADOS = [
        'pendiente'   => ['label' => 'Pendiente',   'color' => 'warning'],
        'verificado'  => ['label' => 'Verificado',  'color' => 'success'],
        'rechazado'   => ['label' => 'Rechazado',   'color' => 'danger'],
    ];

    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class);
    }

    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** Devuelve los paquetes incluidos en este cobro */
    public function packages()
    {
        return LogisticsPackage::withoutGlobalScopes()
            ->whereIn('id', $this->package_ids ?? [])
            ->get();
    }
}
