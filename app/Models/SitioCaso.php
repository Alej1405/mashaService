<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Caso del portafolio. No sale del ERP hasta que 'publicable' esta en true. */
class SitioCaso extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_casos';

    protected $fillable = [
        'empresa_id', 'slug', 'titulo', 'cliente', 'sector', 'servicio',
        'resumen', 'problema', 'solucion', 'resultado', 'metricas',
        'enlace_sitio', 'imagen_portada', 'portada_ancho', 'portada_alto',
        'publicable', 'destacado', 'sort_order', 'activo',
    ];

    protected $casts = [
        'metricas'      => 'array',
        'publicable'    => 'boolean',
        'destacado'     => 'boolean',
        'sort_order'    => 'integer',
        'portada_ancho' => 'integer',
        'portada_alto'  => 'integer',
        'activo'        => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $caso) {
            if (empty($caso->slug)) {
                $caso->slug = Str::slug($caso->titulo);
            }

            if ($caso->isDirty('imagen_portada')) {
                [$caso->portada_ancho, $caso->portada_alto] = \App\Support\ImagenPublica::medidas($caso->imagen_portada);
            }
        });
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(SitioCasoImagen::class, 'sitio_caso_id')->orderBy('sort_order');
    }
}
