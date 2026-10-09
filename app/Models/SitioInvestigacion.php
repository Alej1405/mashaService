<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/** Una investigación del laboratorio: un microservicio que se usa desde la web. */
class SitioInvestigacion extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_investigaciones';

    protected $fillable = [
        'empresa_id', 'titulo', 'slug', 'resumen', 'para_quien', 'cuerpo', 'stack', 'estado',
        'herramienta', 'servicio_url', 'imagen', 'seo_titulo', 'seo_descripcion', 'sort_order', 'activo',
    ];

    protected $casts = [
        'stack'      => 'array',
        'sort_order' => 'integer',
        'activo'     => 'boolean',
    ];
}
