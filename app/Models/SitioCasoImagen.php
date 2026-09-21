<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Imagen de la galeria de un caso. Guarda ancho y alto al subirla para que
 * el front reserve el espacio y la pagina no salte mientras carga.
 */
class SitioCasoImagen extends Model
{
    protected $table = 'sitio_caso_imagenes';

    protected $fillable = ['sitio_caso_id', 'imagen', 'texto_alt', 'ancho', 'alto', 'sort_order'];

    protected $casts = ['ancho' => 'integer', 'alto' => 'integer', 'sort_order' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (self $imagen) {
            if ($imagen->isDirty('imagen')) {
                [$imagen->ancho, $imagen->alto] = \App\Support\ImagenPublica::medidas($imagen->imagen);
            }
        });
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(SitioCaso::class, 'sitio_caso_id');
    }
}
