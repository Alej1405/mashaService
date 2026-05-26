<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasEmpresa;

    public const TIPOS = [
        'natural'  => 'Persona natural',
        'juridica' => 'Empresa / RUC',
    ];

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'apellido',
        'razon_social',
        'tipo_persona',
        'tipo_identificacion',
        'numero_identificacion',
        'email',
        'password',
        'email_verified_at',
        'telefono',
        'direccion',
        'es_exportador',
        'pais_destino',
        'cuenta_contable_id',
        'activo',
        'is_super_admin',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'es_exportador'     => 'boolean',
        'activo'            => 'boolean',
        'is_super_admin'    => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $year = now()->year;
                $last = self::where('empresa_id', $model->empresa_id)->latest('id')->first();
                $next = $last ? ((int) substr($last->codigo, -5)) + 1 : 1;
                $model->codigo = "CLI-{$year}-" . str_pad($next, 5, '0', STR_PAD_LEFT);
            }

            if (empty($model->cuenta_contable_id)) {
                try {
                    $empresaId = $model->empresa_id
                        ?? (function_exists('filament') ? \Filament\Facades\Filament::getTenant()?->id : null);
                    if ($empresaId) {
                        $cuenta = \App\Services\AccountingService::getMapeo($empresaId, 'global', 'venta_credito');
                        $model->cuenta_contable_id = $cuenta->id;
                    }
                } catch (\Exception) {
                    \Illuminate\Support\Facades\Log::warning("Customer accounting map failed: {$model->codigo}");
                }
            }
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        if ($this->tipo_persona === 'juridica' && $this->razon_social) {
            return $this->razon_social;
        }
        return trim($this->nombre . ' ' . ($this->apellido ?? ''));
    }

    // ── Relaciones ERP ────────────────────────────────────────────────────────

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class, 'cuenta_contable_id');
    }

    // ── Relaciones Portal ─────────────────────────────────────────────────────

    public function addresses(): HasMany
    {
        return $this->hasMany(StoreAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }

    public function serviceContracts(): HasMany
    {
        return $this->hasMany(ServiceContract::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(StoreCustomerCompany::class);
    }
}
