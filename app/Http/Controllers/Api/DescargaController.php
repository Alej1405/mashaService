<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Salida a internet para el servicio de puntaje.
 *
 * El microservicio que califica páginas vive en el VPS de Hostinger, y desde
 * ahí no se alcanza el cPanel donde están rivet-ec.com, zarku.ec y
 * linkcargoecuador.com: la red del hosting los bloquea. Justo los sitios de
 * nuestros clientes, que son los que más se van a consultar.
 *
 * Este servidor sí los alcanza, así que hace de segunda salida: el
 * microservicio pide por aquí solo cuando la conexión directa falla.
 *
 * No guarda nada y no interpreta nada: descarga y devuelve el HTML tal cual.
 * El análisis sigue entero en el microservicio.
 *
 * ── Por qué está blindado igual que el otro ─────────────────────────────────
 *
 * Un endpoint que descarga la URL que le manden sirve para alcanzar servicios
 * internos de este mismo servidor —que aquí son la base de datos y el panel—,
 * así que vale el mismo cuidado aunque haga falta token: solo http y https,
 * se rechaza cualquier IP que no sea pública, y las redirecciones se vuelven
 * a comprobar una por una.
 */
class DescargaController extends Controller
{
    /** Con 400 KB sobra: lo que se analiza vive en el <head>. */
    private const MAX_BYTES = 400_000;

    private const TIMEOUT = 8;

    private const MAX_SALTOS = 3;

    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'url' => ['required', 'string', 'max:300', 'url:http,https'],
        ]);

        $url = $datos['url'];

        for ($salto = 0; $salto <= self::MAX_SALTOS; $salto++) {
            if (! $this->hostEsPublico($url)) {
                return response()->json(['error' => 'Esa dirección no es pública.'], 422);
            }

            try {
                $r = Http::withoutRedirecting()
                    ->timeout(self::TIMEOUT)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) '
                            . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36 '
                            . 'MashaCorp/1.0 (+https://mashaec.net)',
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])
                    ->get($url);
            } catch (\Throwable $e) {
                return response()->json(['error' => 'No se pudo abrir esa página.'], 502);
            }

            if ($r->redirect() && $r->header('Location')) {
                $url = $this->resolver($url, $r->header('Location'));
                continue;
            }

            if ($r->status() >= 400) {
                return response()->json(['error' => "Esa página respondió {$r->status()}."], 502);
            }

            return response()->json([
                'html' => substr($r->body(), 0, self::MAX_BYTES),
                'url'  => $url,
            ]);
        }

        return response()->json(['error' => 'Esa página redirige demasiadas veces.'], 502);
    }

    /** Falso si el host resuelve a cualquier dirección que no sea pública. */
    private function hostEsPublico(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        $direcciones = array_merge(
            gethostbynamel($host) ?: [],
            array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
        );

        if (! $direcciones) {
            return false;
        }

        foreach ($direcciones as $ip) {
            // FILTER_FLAG_NO_PRIV_RANGE y NO_RES_RANGE dejan fuera loopback,
            // redes privadas, enlace local y todo lo reservado.
            $publica = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );

            if ($publica === false) {
                return false;
            }
        }

        return true;
    }

    /** Une una redirección relativa con la dirección de la que vino. */
    private function resolver(string $base, string $destino): string
    {
        if (preg_match('#^https?://#i', $destino)) {
            return $destino;
        }

        $partes = parse_url($base);
        $raiz = $partes['scheme'] . '://' . $partes['host'];

        return $raiz . '/' . ltrim($destino, '/');
    }
}
