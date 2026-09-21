<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Respuesta de la API del sitio: JSON + ETag + Cache-Control.
 *
 * El ETag sale del sello del contenido, no del cuerpo: si nadie edito nada
 * en el panel, SvelteKit revalida y recibe un 304 sin cuerpo. El 'load' de
 * la pagina sigue funcionando igual y no se paga el payload de nuevo.
 */
class RespuestaSitio
{
    public static function json(Request $request, mixed $datos, string $sello, int $estado = 200): JsonResponse
    {
        $respuesta = response()->json($datos, $estado);

        if ($estado !== 200) {
            return $respuesta;
        }

        $respuesta->setEtag(md5($sello . '|' . $request->path() . '|' . $request->getQueryString()));
        $respuesta->setPublic();
        $respuesta->setMaxAge(config('sitio.max_age'));

        // Si el ETag del cliente coincide, devuelve 304 y descarta el cuerpo.
        $respuesta->isNotModified($request);

        return $respuesta;
    }
}
