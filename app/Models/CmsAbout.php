<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsAbout extends Model
{
    use HasEmpresa;

    protected $table = 'cms_abouts';

    protected $fillable = [
        'empresa_id', 'titulo', 'cuerpo', 'imagen', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];
}
