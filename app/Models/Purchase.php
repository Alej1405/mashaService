<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEmpresa;

class Purchase extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'supplier_id',
        'number',
        'numero_factura',
        'date',
        'tipo_pago',
        'fecha_vencimiento',
        'subtotal',
        'iva',
        'total',
        'status',
        'notas',
        'journal_entry_id',
        'error_contable',
        'error_contable_msg',
        'cash_register_id',
        'bank_account_id',
        'credit_card_id',
        'forma_pago',
        'confirmado_por',
        'confirmado_at',
        'origen',
        'clave_acceso',
        'xml_path',
        'forma_pago_sri',
        'plazo_dias',
        'requiere_forma_pago',
    ];

    public const ORIGEN_MANUAL = 'manual';
    public const ORIGEN_CORREO = 'correo';
    public const ORIGEN_TELEGRAM = 'telegram';
    public const BORRADOR = 'borrador';
    public const CONFIRMADO = 'confirmado';

    protected $casts = [
        'date' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:4',
        'iva' => 'decimal:4',
        'total' => 'decimal:4',
        'confirmado_at' => 'datetime',
        'requiere_forma_pago' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->number)) {
                $year = now()->year;
                $last = self::where('empresa_id', $model->empresa_id)
                    ->whereYear('date', $year)
                    ->latest('id')
                    ->first();
                
                $next = $last ? ((int) substr($last->number, -5)) + 1 : 1;
                $model->number = "COM-{$year}-" . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /** Líneas que todavía no se sabe qué son: ni ítem de inventario ni tipo de gasto. */
    public function itemsPorConfigurar(): HasMany
    {
        return $this->items()->whereNull('inventory_item_id')
            ->where(fn ($q) => $q->whereNull('producto_proveedor_id')
                ->orWhereHas('productoProveedor', fn ($p) => $p->whereNull('tipo_gasto_id')));
    }

    /** Lista para confirmar: sabe cómo se pagó y qué es cada línea. */
    public function listaParaConfirmar(): bool
    {
        return $this->status === self::BORRADOR
            && ! $this->requiere_forma_pago
            && ! $this->itemsPorConfigurar()->exists();
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }
}
