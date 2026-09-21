<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Imagenes del disco publico: su URL completa y sus medidas.
 *
 * Las medidas se leen al guardar, nunca al servir: el front necesita
 * ancho y alto para reservar el espacio y que la pagina no salte, y
 * abrir el archivo en cada peticion seria pagarlo dos veces.
 */
class ImagenPublica
{
    public static function url(?string $ruta): ?string
    {
        return $ruta ? Storage::disk('public')->url($ruta) : null;
    }

    /** @return array{0: int|null, 1: int|null} [ancho, alto] */
    public static function medidas(?string $ruta): array
    {
        if (! $ruta) {
            return [null, null];
        }

        $disco = Storage::disk('public');

        if (! $disco->exists($ruta)) {
            return [null, null];
        }

        $medidas = @getimagesize($disco->path($ruta));

        return $medidas ? [(int) $medidas[0], (int) $medidas[1]] : [null, null];
    }
}
