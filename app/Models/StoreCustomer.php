<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class StoreCustomer extends Authenticatable
{
    use HasApiTokens, HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'customer_id',
        'tipo',
        'razon_social',
        'nombre',
        'apellido',
        'email',
        'telefono',
        'cedula_ruc',
        'password',
        'email_verified_at',
        'activo',
        'is_super_admin',
    ];

    public const TIPOS = [
        'persona'  => 'Persona natural',
        'empresa'  => 'Empresa / RUC',
    ];

    /** Nombre para mostrar: razón social si es empresa, nombre completo si es persona. */
    public function getNombreCompletoAttribute(): string
    {
        if ($this->tipo === 'empresa' && $this->razon_social) {
            return $this->razon_social;
        }
        return trim($this->nombre . ' ' . ($this->apellido ?? ''));
    }

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo'            => 'boolean',
        'is_super_admin'    => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Cliente ERP vinculado (para facturación y contabilidad). */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

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
}
