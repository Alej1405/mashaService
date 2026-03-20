<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsHero extends Model
{
    use HasEmpresa;

    protected $table = 'cms_heroes';

    protected $fillable = [
        'empresa_id', 'titulo', 'subtitulo', 'descripcion',
        'imagen', 'cta_texto', 'cta_url', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];
}
