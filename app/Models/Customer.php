<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEmpresa;

class Customer extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'tipo_persona',
        'tipo_identificacion',
        'numero_identificacion',
        'email',
        'telefono',
        'direccion',
        'es_exportador',
        'pais_destino',
        'cuenta_contable_id',
        'activo',
    ];

    protected $casts = [
        'es_exportador' => 'boolean',
        'activo' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $year = now()->year;
                $last = self::where('empresa_id', $model->empresa_id)
                    ->latest('id')
                    ->first();
                
                $next = $last ? ((int) substr($last->codigo, -5)) + 1 : 1;
                $model->codigo = "CLI-{$year}-" . str_pad($next, 5, '0', STR_PAD_LEFT);
            }

            // Asignación automática de cuenta contable (basado en InventoryItem)
            if (empty($model->cuenta_contable_id)) {
                try {
                    $empresaId = $model->empresa_id ?? (function_exists('filament') ? \Filament\Facades\Filament::getTenant()?->id : null);
                    if ($empresaId) {
                        $cuenta = \App\Services\AccountingService::getMapeo($empresaId, 'global', 'venta_credito');
                        $model->cuenta_contable_id = $cuenta->id;
                    }
                } catch (\Exception $e) {
                    // Fallback silencioso si el mapeo no existe
                    \Illuminate\Support\Facades\Log::warning("Customer accounting map failed for customer {$model->codigo}");
                }
            }
        });
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class, 'cuenta_contable_id');
    }
}
