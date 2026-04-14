<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsProduct extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'precio',
        'unidad_precio',
        'imagen',
        'categoria',
        'caracteristicas',
        'icono',
        'sort_order',
        'activo',
    ];

    protected $casts = [
        'activo'          => 'boolean',
        'precio'          => 'decimal:4',
        'caracteristicas' => 'array',
    ];
}
