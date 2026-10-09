<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Una seccion autonoma del sitio: /desarrollo, /fotografia, /laboratorio, /proceso. */
class SitioPagina extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_paginas';

    protected $fillable = [
        'empresa_id', 'slug', 'titulo', 'subtitulo', 'descripcion',
        'seo_titulo', 'seo_descripcion',
        'imagen', 'cuerpo', 'bloques', 'sort_order', 'activo',
    ];

    protected $casts = [
        'bloques'    => 'array',
        'sort_order' => 'integer',
        'activo'     => 'boolean',
    ];

    /** Los planes que se muestran en esta página. Se editan dentro de ella. */
    public function planes(): HasMany
    {
        return $this->hasMany(SitioPlan::class, 'pagina_slug', 'slug')
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('sort_order');
    }
}
