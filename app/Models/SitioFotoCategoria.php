<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Una categoría de fotografía: es una tarjeta de «Qué fotografiamos». */
class SitioFotoCategoria extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_foto_categorias';

    protected $fillable = ['empresa_id', 'nombre', 'slug', 'descripcion', 'para', 'sort_order', 'activo'];

    protected $casts = [
        'sort_order' => 'integer',
        'activo'     => 'boolean',
    ];

    public function albumes(): HasMany
    {
        return $this->hasMany(SitioFotoAlbum::class, 'categoria_id')->orderBy('sort_order');
    }
}
