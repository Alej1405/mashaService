<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/** Una seccion autonoma del sitio: /desarrollo, /fotografia, /laboratorio, /proceso. */
class SitioPagina extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_paginas';

    protected $fillable = [
        'empresa_id', 'slug', 'titulo', 'subtitulo', 'descripcion',
        'imagen', 'cuerpo', 'bloques', 'sort_order', 'activo',
    ];

    protected $casts = [
        'bloques'    => 'array',
        'sort_order' => 'integer',
        'activo'     => 'boolean',
    ];
}
