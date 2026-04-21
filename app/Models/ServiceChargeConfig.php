<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class ServiceChargeConfig extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'monto',
        'tipo',
        'iva_pct',
        'activo',
    ];

    protected $casts = [
        'monto'  => 'decimal:2',
        'iva_pct' => 'integer',
        'activo' => 'boolean',
    ];

    public function servicePackages(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServicePackage::class, 'service_package_charge_config');
    }
}
