<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un álbum de fotos del portafolio. Las fotos van en orden en `fotos`. */
class SitioFotoAlbum extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_foto_albumes';

    protected $fillable = [
        'empresa_id', 'categoria_id', 'titulo', 'slug', 'descripcion',
        'fecha', 'portada', 'fotos', 'sort_order', 'activo',
    ];

    protected $casts = [
        'fotos'      => 'array',
        'fecha'      => 'date',
        'sort_order' => 'integer',
        'activo'     => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(SitioFotoCategoria::class, 'categoria_id');
    }
}
