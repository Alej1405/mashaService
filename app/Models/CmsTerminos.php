<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsTerminos extends Model
{
    use HasEmpresa;

    protected $table = 'cms_terminos';

    protected $fillable = [
        'empresa_id', 'titulo', 'contenido', 'ultima_actualizacion', 'activo',
    ];

    protected $casts = [
        'activo'               => 'boolean',
        'ultima_actualizacion' => 'date',
    ];
}
