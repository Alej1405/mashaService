<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/** Metadatos de una ruta del sitio: titulo, descripcion, OG y JSON-LD. */
class SitioSeo extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_seo';

    protected $fillable = [
        'empresa_id', 'ruta', 'titulo_meta', 'descripcion_meta',
        'og_imagen', 'robots', 'json_ld',
    ];

    protected $casts = ['json_ld' => 'array'];
}
