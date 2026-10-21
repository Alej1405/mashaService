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
    /** Desde la bandeja del portal del SRI: quien registra está frente a la pantalla. */
    public const ORIGEN_PORTAL = 'portal';
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
                // El año es el de la factura, y el siguiente sale del número más alto con ese
                // prefijo: buscar por fecha mezclaba compras viejas registradas hoy y repetía números.
                $year = $model->date ? \Illuminate\Support\Carbon::parse($model->date)->year : now()->year;
                $max = self::withoutGlobalScopes()->where('empresa_id', $model->empresa_id)
                    ->where('number', 'like', "COM-{$year}-%")
                    ->max(\DB::raw('cast(right(number, 5) as integer)'));

                $model->number = "COM-{$year}-" . str_pad(((int) $max) + 1, 5, '0', STR_PAD_LEFT);
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
