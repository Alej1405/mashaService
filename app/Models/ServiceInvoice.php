<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInvoice extends Model
{
    protected $fillable = [
        'empresa_id',
        'numero',
        'periodo',
        'plan',
        'monto',
        'estado',
        'fecha_emision',
        'fecha_vencimiento',
        'fecha_pago',
        'metodo_pago',
        'notas',
    ];

    protected $casts = [
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago'        => 'date',
        'monto'             => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invoice) {
            if (empty($invoice->numero)) {
                $year  = now()->year;
                $count = self::whereYear('created_at', $year)->count() + 1;
                $invoice->numero = 'FACT-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function estadoColor(): string
    {
        return match ($this->estado) {
            'pendiente' => 'warning',
            'pagado'    => 'success',
            'vencido'   => 'danger',
            default     => 'gray',
        };
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            'basic'      => 'Basic',
            'pro'        => 'Pro',
            'enterprise' => 'Enterprise',
            default      => ucfirst($this->plan),
        };
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', 'vencido')
            ->orWhere(fn($q) => $q->where('estado', 'pendiente')->where('fecha_vencimiento', '<', now()));
    }
}
