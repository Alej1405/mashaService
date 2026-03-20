<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsAbout extends Model
{
    use HasEmpresa;

    protected $table = 'cms_abouts';

    protected $fillable = [
        'empresa_id', 'titulo', 'descripcion',
        'por_que_nosotros', 'numeros', 'caracteristicas',
        'imagen', 'activo',
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'por_que_nosotros' => 'array',
        'numeros'          => 'array',
        'caracteristicas'  => 'array',
    ];
}
