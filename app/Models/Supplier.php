<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasEmpresa;

class Supplier extends Model
{
    use HasEmpresa;

    protected $table = 'suppliers';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'nombre_comercial',
        'tipo_persona',
        'tipo_identificacion',
        'numero_identificacion',
        'tipo_proveedor',
        'contacto_principal',
        'telefono_principal',
        'telefono_secundario',
        'correo_principal',
        'correo_secundario',
        'direccion',
        'ciudad',
        'pais',
        'es_importador',
        'pais_origen',
        'cuenta_contable_id',
        'activo',
    ];

    protected $casts = [
        'tipo_proveedor' => 'array',
        'es_importador' => 'boolean',
        'activo' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    public function accountPlan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class, 'cuenta_contable_id');
    }
}
