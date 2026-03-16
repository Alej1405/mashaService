<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class JournalEntry extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id', 'numero', 'fecha', 'descripcion', 'tipo', 'origen', 
        'referencia_tipo', 'referencia_id', 'status', 'total_debe', 'total_haber', 
        'esta_cuadrado', 'creado_por', 'confirmado_por', 'confirmado_at', 
        'anulado_por', 'anulado_at', 'notas'
    ];

    protected $casts = [
        'fecha' => 'date',
        'confirmado_at' => 'datetime',
        'anulado_at' => 'datetime',
        'esta_cuadrado' => 'boolean',
        'total_debe' => 'decimal:2',
        'total_haber' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->numero)) {
                $año = date('Y', strtotime($model->fecha ?? now()));
                $ultimo = static::where('empresa_id', $model->empresa_id)
                    ->whereYear('fecha', $año)
                    ->orderBy('numero', 'desc')
                    ->first();
                
                $secuencial = $ultimo ? (int) substr($ultimo->numero, -5) + 1 : 1;
                $model->numero = "ASI-{$año}-" . str_pad($secuencial, 5, '0', STR_PAD_LEFT);
            }
            
            if (empty($model->creado_por)) {
                $model->creado_por = Auth::id();
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function confirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    public function anulador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function estasCuadrado(): bool
    {
        return bccomp($this->total_debe, $this->total_haber, 2) === 0;
    }

    public function getDiferenciaAttribute()
    {
        return $this->total_debe - $this->total_haber;
    }

    public function scopeConfirmados($query)
    {
        return $query->where('status', 'confirmado');
    }

    public function scopeBorradores($query)
    {
        return $query->where('status', 'borrador');
    }

    public function scopeAnulados($query)
    {
        return $query->where('status', 'anulado');
    }
}
