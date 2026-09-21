<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sitio\GuardarMensajeRequest;
use App\Http\Responses\RespuestaSitio;
use App\Models\CmsClientLogo;
use App\Models\CmsContact;
use App\Models\CmsFaq;
use App\Models\CmsHero;
use App\Models\CmsPost;
use App\Models\CmsTestimonial;
use App\Models\Empresa;
use App\Models\SitioCaso;
use App\Models\SitioMensaje;
use App\Models\SitioNavegacion;
use App\Models\SitioPagina;
use App\Models\SitioPlan;
use App\Models\SitioSeo;
use App\Support\ImagenPublica;
use App\Support\SitioPropio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * API del sitio propio, pensada para el 'load' de SvelteKit.
 *
 * Una peticion por ruta, no una por seccion: el layout pide /layout una vez
 * y cada pagina pide lo suyo completo. Toda respuesta trae ETag derivado del
 * sello del contenido, asi que mientras nadie edite en el panel la
 * revalidacion cuesta un 304 sin cuerpo.
 *
 * Todo es de lectura menos POST /mensajes, que es el formulario de un campo.
 */
class SitioController extends Controller
{
    // ── Endpoints ──────────────────────────────────────────────────────────

    /** Lo que el layout necesita una sola vez: marca, navegacion y pie. */
    public function layout(Request $request, string $slug): JsonResponse
    {
        return $this->responder($request, $slug, 'layout', function (Empresa $empresa) {
            $navegacion = SitioNavegacion::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderBy('sort_order')
                ->get();

            return [
                'empresa'    => $this->empresaBase($empresa),
                'navegacion' => [
                    // 'inferior' es la barra baja del movil: se sirve aparte
                    // para que el front decida que renderiza por pantalla.
                    'superior' => $this->itemsNavegacion($navegacion, 'superior'),
                    'inferior' => $this->itemsNavegacion($navegacion, 'inferior'),
                    'pie'      => $this->itemsNavegacion($navegacion, 'pie'),
                ],
                'contacto'   => $this->contacto($empresa),
            ];
        });
    }

    /** Portada: hero, las tres fuerzas, casos abiertos, prueba social y FAQ. */
    public function inicio(Request $request, string $slug): JsonResponse
    {
        return $this->responder($request, $slug, 'inicio', function (Empresa $empresa) {
            $hero = CmsHero::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->first();

            $fuerzas = SitioPagina::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->whereIn('slug', ['desarrollo', 'fotografia', 'laboratorio'])
                ->orderBy('sort_order')
                ->get()
                ->map(fn (SitioPagina $p) => $this->paginaResumen($p))
                ->all();

            return [
                'hero' => $hero ? [
                    'titulo'      => $hero->titulo,
                    'subtitulo'   => $hero->subtitulo,
                    'descripcion' => $hero->descripcion,
                    'imagen'      => ImagenPublica::url($hero->imagen),
                    'cta_texto'   => $hero->cta_texto,
                    'cta_url'     => $hero->cta_url,
                ] : null,
                'fuerzas' => $fuerzas,
                // Completos y con galeria: en el inicio el caso se expande
                // sin navegar, asi que el front ya tiene que tener el cuerpo.
                'casos'        => $this->casos($empresa, servicio: null, limite: 6, conGaleria: true),
                'logos'        => $this->logos($empresa),
                'testimonios'  => $this->testimonios($empresa),
                'faq'          => $this->faq($empresa),
                'seo'          => $this->seo($empresa, '/'),
            ];
        });
    }

    /** Seccion autonoma: /desarrollo, /fotografia, /laboratorio, /proceso. */
    public function pagina(Request $request, string $slug, string $pagina): JsonResponse
    {
        return $this->responder($request, $slug, "pagina:{$pagina}", function (Empresa $empresa) use ($pagina) {
            $registro = SitioPagina::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('slug', $pagina)
                ->where('activo', true)
                ->first();

            if (! $registro) {
                return null;
            }

            $esServicio = in_array($pagina, config('sitio.servicios'), true);

            return [
                'pagina' => [
                    'slug'        => $registro->slug,
                    'titulo'      => $registro->titulo,
                    'subtitulo'   => $registro->subtitulo,
                    'descripcion' => $registro->descripcion,
                    'imagen'      => ImagenPublica::url($registro->imagen),
                    'cuerpo'      => $registro->cuerpo,
                    'bloques'     => $registro->bloques ?? [],
                ],
                'casos'     => $esServicio ? $this->casos($empresa, servicio: $pagina, limite: 24, conGaleria: true) : [],
                'planes'    => $this->planes($empresa, $pagina),
                'articulos' => $pagina === 'laboratorio' ? $this->articulosResumen($empresa) : [],
                'seo'       => $this->seo($empresa, '/' . $pagina),
            ];
        });
    }

    /** Un caso del portafolio por su slug. */
    public function caso(Request $request, string $slug, string $caso): JsonResponse
    {
        return $this->responder($request, $slug, "caso:{$caso}", function (Empresa $empresa) use ($caso) {
            $registro = SitioCaso::withoutGlobalScopes()
                ->with('imagenes')
                ->where('empresa_id', $empresa->id)
                ->where('slug', $caso)
                ->where('activo', true)
                ->where('publicable', true)
                ->first();

            if (! $registro) {
                return null;
            }

            return [
                'caso' => $this->casoCompleto($registro, conGaleria: true),
                'seo'  => $this->seo($empresa, '/casos/' . $caso),
            ];
        });
    }

    /** Indice del Laboratorio. */
    public function articulos(Request $request, string $slug): JsonResponse
    {
        return $this->responder($request, $slug, 'articulos', function (Empresa $empresa) {
            return [
                'articulos' => $this->articulosResumen($empresa),
                'seo'       => $this->seo($empresa, '/laboratorio'),
            ];
        });
    }

    /** Un articulo completo. */
    public function articulo(Request $request, string $slug, string $articulo): JsonResponse
    {
        return $this->responder($request, $slug, "articulo:{$articulo}", function (Empresa $empresa) use ($articulo) {
            $post = CmsPost::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('slug', $articulo)
                ->where('activo', true)
                ->whereNotNull('publicado_en')
                ->where('publicado_en', '<=', now())
                ->first();

            if (! $post) {
                return null;
            }

            return [
                'articulo' => [
                    'titulo'          => $post->titulo,
                    'slug'            => $post->slug,
                    'resumen'         => $post->resumen,
                    'serie'           => $post->serie,
                    'minutos_lectura' => $post->minutos_lectura,
                    'contenido'       => $post->contenido,
                    'imagen'          => ImagenPublica::url($post->imagen),
                    'publicado_en'    => $post->publicado_en?->toISOString(),
                ],
                'seo' => $this->seo($empresa, '/laboratorio/' . $articulo),
            ];
        });
    }

    /** Formulario de un solo campo. Unica escritura del sitio. */
    public function mensaje(GuardarMensajeRequest $request, string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);

        $mensaje = SitioMensaje::create([
            'empresa_id' => $empresa->id,
            'contacto'   => $request->string('contacto')->trim()->toString(),
            'mensaje'    => $request->input('mensaje'),
            'origen'     => $request->input('origen'),
            'ip_hash'    => hash('sha256', (string) $request->ip()),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json(['ok' => true, 'id' => $mensaje->id], 201);
    }

    // ── Armado de respuesta ────────────────────────────────────────────────

    /**
     * Resuelve empresa, cachea el payload con el sello vigente y responde
     * con ETag. Si el callback devuelve null, es un 404 del contenido.
     */
    private function responder(Request $request, string $slug, string $parte, callable $callback): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $sello   = SitioPropio::sello($slug);

        $datos = $this->cacheado("sitio:{$slug}:{$sello}:{$parte}", fn () => $callback($empresa));

        if ($datos === null) {
            return response()->json(['error' => 'No encontrado.'], 404);
        }

        return RespuestaSitio::json($request, $datos, $sello);
    }

    private function empresa(string $slug): Empresa
    {
        return Empresa::where('slug', $slug)->where('activo', true)->firstOrFail();
    }

    /**
     * Cache::remember() trata null como fallo de cache y reconsulta cada vez.
     * Envolver en ['v' => …] hace que el null tambien se guarde.
     */
    private function cacheado(string $clave, callable $callback): mixed
    {
        $resultado = Cache::remember($clave, config('sitio.ttl'), fn () => ['v' => $callback()]);

        return $resultado['v'];
    }

    // ── Piezas reutilizables ───────────────────────────────────────────────

    private function empresaBase(Empresa $empresa): array
    {
        return [
            'nombre' => $empresa->name,
            'slug'   => $empresa->slug,
            'logo'   => ImagenPublica::url($empresa->logo_path),
        ];
    }

    /** @param \Illuminate\Support\Collection<int, SitioNavegacion> $items */
    private function itemsNavegacion($items, string $ubicacion): array
    {
        return $items
            ->where('ubicacion', $ubicacion)
            ->map(fn (SitioNavegacion $i) => [
                'etiqueta'    => $i->etiqueta,
                'ruta'        => $i->ruta,
                'icono'       => $i->icono,
                'dispositivo' => $i->dispositivo,
            ])
            ->values()
            ->all();
    }

    private function contacto(Empresa $empresa): ?array
    {
        $contacto = CmsContact::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->first();

        if (! $contacto) {
            return null;
        }

        return [
            'direccion' => $contacto->direccion,
            'telefono'  => $contacto->telefono,
            'email'     => $contacto->email,
            'whatsapp'  => $contacto->whatsapp,
            'redes'     => array_filter([
                'facebook'  => $contacto->facebook,
                'instagram' => $contacto->instagram,
                'linkedin'  => $contacto->linkedin,
                'youtube'   => $contacto->youtube,
                'tiktok'    => $contacto->tiktok,
            ]),
        ];
    }

    private function paginaResumen(SitioPagina $pagina): array
    {
        return [
            'slug'        => $pagina->slug,
            'titulo'      => $pagina->titulo,
            'subtitulo'   => $pagina->subtitulo,
            'descripcion' => $pagina->descripcion,
            'imagen'      => ImagenPublica::url($pagina->imagen),
            'ruta'        => '/' . $pagina->slug,
        ];
    }

    private function casos(Empresa $empresa, ?string $servicio, int $limite, bool $conGaleria): array
    {
        $consulta = SitioCaso::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->where('publicable', true);

        if ($servicio) {
            $consulta->where('servicio', $servicio);
        }

        if ($conGaleria) {
            $consulta->with('imagenes');
        }

        return $consulta
            // El destacado va primero: en el sitio abre expandido.
            ->orderByDesc('destacado')
            ->orderBy('sort_order')
            ->limit($limite)
            ->get()
            ->map(fn (SitioCaso $c) => $this->casoCompleto($c, $conGaleria))
            ->all();
    }

    private function casoCompleto(SitioCaso $caso, bool $conGaleria): array
    {
        return [
            'slug'      => $caso->slug,
            'titulo'    => $caso->titulo,
            'cliente'   => $caso->cliente,
            'sector'    => $caso->sector,
            'servicio'  => $caso->servicio,
            'resumen'   => $caso->resumen,
            'problema'  => $caso->problema,
            'solucion'  => $caso->solucion,
            'resultado' => $caso->resultado,
            'metricas'  => $caso->metricas ?? [],
            'enlace'    => $caso->enlace_sitio,
            'destacado' => $caso->destacado,
            'portada'   => $caso->imagen_portada ? [
                'url'   => ImagenPublica::url($caso->imagen_portada),
                'ancho' => $caso->portada_ancho,
                'alto'  => $caso->portada_alto,
            ] : null,
            'galeria' => $conGaleria
                ? $caso->imagenes->map(fn ($i) => [
                    'url'   => ImagenPublica::url($i->imagen),
                    'alt'   => $i->texto_alt,
                    'ancho' => $i->ancho,
                    'alto'  => $i->alto,
                ])->all()
                : [],
        ];
    }

    private function planes(Empresa $empresa, string $paginaSlug): array
    {
        return SitioPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('pagina_slug', $paginaSlug)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SitioPlan $p) => [
                'nombre'       => $p->nombre,
                'descripcion'  => $p->descripcion,
                'precio_desde' => $p->precio_desde !== null ? (float) $p->precio_desde : null,
                'moneda'       => $p->moneda,
                'periodicidad' => $p->periodicidad,
                'incluye'      => $p->incluye ?? [],
                'nota'         => $p->nota,
                'destacado'    => $p->destacado,
            ])
            ->all();
    }

    private function articulosResumen(Empresa $empresa): array
    {
        return CmsPost::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->whereNotNull('publicado_en')
            ->where('publicado_en', '<=', now())
            ->orderByDesc('destacado')
            ->orderByDesc('publicado_en')
            ->get()
            ->map(fn (CmsPost $p) => [
                'titulo'          => $p->titulo,
                'slug'            => $p->slug,
                'resumen'         => $p->resumen ?: mb_substr(strip_tags($p->contenido), 0, 200),
                'serie'           => $p->serie,
                'minutos_lectura' => $p->minutos_lectura,
                'destacado'       => $p->destacado,
                'imagen'          => ImagenPublica::url($p->imagen),
                'publicado_en'    => $p->publicado_en?->toISOString(),
            ])
            ->all();
    }

    private function logos(Empresa $empresa): array
    {
        return CmsClientLogo::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($l) => [
                'nombre' => $l->nombre,
                'logo'   => ImagenPublica::url($l->logo),
                'url'    => $l->url,
            ])
            ->all();
    }

    private function testimonios(Empresa $empresa): array
    {
        return CmsTestimonial::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($t) => [
                'autor_nombre'  => $t->autor_nombre,
                'autor_cargo'   => $t->autor_cargo,
                'autor_empresa' => $t->autor_empresa,
                'autor_foto'    => ImagenPublica::url($t->autor_foto),
                'contenido'     => $t->contenido,
                'estrellas'     => $t->estrellas,
            ])
            ->all();
    }

    private function faq(Empresa $empresa): array
    {
        return CmsFaq::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($f) => ['pregunta' => $f->pregunta, 'respuesta' => $f->respuesta])
            ->all();
    }

    private function seo(Empresa $empresa, string $ruta): array
    {
        $seo = SitioSeo::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('ruta', $ruta)
            ->first();

        return [
            'titulo'      => $seo?->titulo_meta ?? $empresa->name,
            'descripcion' => $seo?->descripcion_meta,
            'og_imagen'   => ImagenPublica::url($seo?->og_imagen) ?? ImagenPublica::url($empresa->logo_path),
            'robots'      => $seo?->robots ?? 'index,follow',
            'json_ld'     => $seo?->json_ld,
        ];
    }
}
