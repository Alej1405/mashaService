<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Precio publico de una seccion. No tiene relacion con ServicePlan, que es
 * la facturacion interna del ERP a las empresas.
 */
class SitioPlan extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_planes';

    protected $fillable = [
        'empresa_id', 'pagina_slug', 'nombre', 'descripcion', 'precio_desde',
        'moneda', 'periodicidad', 'incluye', 'nota', 'destacado', 'sort_order', 'activo',
    ];

    protected $casts = [
        'incluye'      => 'array',
        'precio_desde' => 'decimal:2',
        'destacado'    => 'boolean',
        'sort_order'   => 'integer',
        'activo'       => 'boolean',
    ];
}
