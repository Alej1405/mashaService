<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsService extends Model
{
    use HasEmpresa;

    protected $table = 'cms_services';

    protected $fillable = [
        'empresa_id', 'titulo', 'descripcion', 'caracteristicas', 'icono', 'imagen', 'sort_order', 'activo',
    ];

    protected $casts = ['activo' => 'boolean', 'sort_order' => 'integer', 'caracteristicas' => 'array'];
}
