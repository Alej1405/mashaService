<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve la Empresa del sitio propio y maneja el sello de cache.
 *
 * El sello es un numero que cambia cada vez que se guarda contenido del
 * sitio. Va dentro de la clave de cache y dentro del ETag, asi que editar
 * en el panel invalida todo de una sin tener que recordar que clave toco
 * cada modelo.
 */
class SitioPropio
{
    private const CLAVE_SELLO = 'sitio:%s:sello';

    public static function slug(): string
    {
        return (string) config('sitio.empresa_slug');
    }

    /** La Empresa propia, o null si todavia no existe en la base. */
    public static function empresa(): ?Empresa
    {
        return Empresa::where('slug', self::slug())->first();
    }

    /** Id de la Empresa propia. Lanza si no existe: sin ella no hay sitio. */
    public static function empresaId(): int
    {
        $empresa = self::empresa();

        if (! $empresa) {
            throw new \RuntimeException(
                "No existe la empresa del sitio propio (slug '" . self::slug() . "'). " .
                "Corre: php artisan db:seed --class=SitioSeeder"
            );
        }

        return $empresa->id;
    }

    /** Agrega la empresa propia a los datos de un formulario del panel admin. */
    public static function conEmpresa(array $datos): array
    {
        $datos['empresa_id'] = self::empresaId();

        return $datos;
    }

    /** Sello actual del contenido de esa empresa. */
    public static function sello(string $slug): string
    {
        return (string) Cache::rememberForever(sprintf(self::CLAVE_SELLO, $slug), fn () => (string) now()->getTimestampMs());
    }

    /** Renueva el sello: todo lo cacheado de ese sitio queda obsoleto. */
    public static function renovarSello(string $slug): void
    {
        Cache::forever(sprintf(self::CLAVE_SELLO, $slug), (string) now()->getTimestampMs());
    }
}
