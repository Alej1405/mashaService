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
        'nombre',
        'apellido',
        'email',
        'telefono',
        'password',
        'email_verified_at',
        'activo',
        'is_super_admin',
    ];

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
