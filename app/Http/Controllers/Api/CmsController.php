<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsAbout;
use App\Models\CmsClientLogo;
use App\Models\CmsContact;
use App\Models\CmsFaq;
use App\Models\CmsHero;
use App\Models\CmsPost;
use App\Models\CmsService;
use App\Models\CmsTeamMember;
use App\Models\CmsTerminos;
use App\Models\CmsTestimonial;
use App\Models\Customer;
use App\Models\CustomerMenuItem;
use App\Models\Empresa;
use App\Models\StoreProduct;
use App\Models\ServiceDesign;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CmsController extends Controller
{
    private const TTL = 600; // 10 minutos

    /** Resuelve la empresa por slug o devuelve 404. */
    private function empresa(string $slug): Empresa
    {
        return Empresa::where('slug', $slug)->where('activo', true)->firstOrFail();
    }

    /** Convierte rutas de imagen a URLs públicas completas. */
    private function imageUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Cache::remember() no almacena null (lo trata como cache miss).
     * Envuelve el resultado en ['v' => ...] para que null se cachee correctamente.
     */
    private function cached(string $key, int $ttl, callable $callback): mixed
    {
        $result = Cache::remember($key, $ttl, fn () => ['v' => $callback()]);
        return $result['v'];
    }

    // ── Endpoints ──────────────────────────────────────────────────────────

    public function hero(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:hero", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);
            $hero    = CmsHero::withoutGlobalScopes()
                ->select(['titulo', 'subtitulo', 'descripcion', 'imagen', 'cta_texto', 'cta_url'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->first();

            if (! $hero) {
                return null;
            }

            return [
                'titulo'      => $hero->titulo,
                'subtitulo'   => $hero->subtitulo,
                'descripcion' => $hero->descripcion,
                'imagen'      => $this->imageUrl($hero->imagen),
                'cta_texto'   => $hero->cta_texto,
                'cta_url'     => $hero->cta_url,
            ];
        });

        return response()->json($data);
    }

    public function about(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:about", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);
            $about   = CmsAbout::withoutGlobalScopes()
                ->select(['titulo', 'descripcion', 'imagen', 'por_que_nosotros', 'numeros', 'caracteristicas'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->first();

            if (! $about) {
                return null;
            }

            return [
                'titulo'           => $about->titulo,
                'descripcion'      => $about->descripcion,
                'imagen'           => $this->imageUrl($about->imagen),
                'por_que_nosotros' => $about->por_que_nosotros ?? [],
                'numeros'          => $about->numeros          ?? [],
                'caracteristicas'  => $about->caracteristicas  ?? [],
            ];
        });

        return response()->json($data);
    }

    public function services(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:services", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);
            return $this->buildServices($empresa);
        });

        return response()->json($data);
    }

    public function products(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:products", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);
            return $this->buildProducts($empresa);
        });

        return response()->json($data);
    }

    public function team(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:team", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);

            return CmsTeamMember::withoutGlobalScopes()
                ->select(['id', 'nombre', 'cargo', 'bio', 'foto'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($m) => [
                    'id'     => $m->id,
                    'nombre' => $m->nombre,
                    'cargo'  => $m->cargo,
                    'bio'    => $m->bio,
                    'foto'   => $this->imageUrl($m->foto),
                ])->all();
        });

        return response()->json($data);
    }

    public function clients(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:clients", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);

            return CmsClientLogo::withoutGlobalScopes()
                ->select(['id', 'nombre', 'logo', 'url'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($c) => [
                    'id'     => $c->id,
                    'nombre' => $c->nombre,
                    'logo'   => $this->imageUrl($c->logo),
                    'url'    => $c->url,
                ])->all();
        });

        return response()->json($data);
    }

    /**
     * Puntos de venta publicados (cada cliente es un punto de venta). Alimenta el
     * listado/mapa del front. Ojo: NO confundir con clients(), que son los logos de
     * marcas del CMS.
     *
     * Solo se exponen los campos de la ficha pública: el resto del Customer (email,
     * identificación, cuenta contable) es interno y no sale de aquí.
     */
    public function puntosVenta(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:puntos-venta", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);

            return Customer::withoutGlobalScopes()
                ->select([
                    'id', 'slug', 'nombre', 'apellido', 'razon_social', 'tipo_persona',
                    'descripcion_web', 'horario', 'logo', 'banner', 'direccion',
                    'telefono', 'latitud', 'longitud', 'menu_activo',
                ])
                ->where('empresa_id', $empresa->id)
                ->where('publicado', true)
                ->where('activo', true)
                ->whereNotNull('slug')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Customer $c) => $this->puntoVentaPayload($c))
                ->all();
        });

        return response()->json($data);
    }

    /**
     * Ficha pública de un punto de venta + su carta. El menú solo viaja si el punto
     * lo tiene activo; si no, `menu` va vacío y el front no dibuja la sección.
     */
    public function puntoVenta(string $slug, string $punto): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:punto-venta:{$punto}", self::TTL, function () use ($slug, $punto) {
            $empresa = $this->empresa($slug);

            $cliente = Customer::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('slug', $punto)
                ->where('publicado', true)
                ->where('activo', true)
                ->first();

            if (! $cliente) {
                return null;
            }

            $menu = $cliente->menu_activo
                ? $cliente->menuItems()
                    ->withoutGlobalScopes()
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->get()
                    ->map(fn (CustomerMenuItem $i) => [
                        'id'          => $i->id,
                        'nombre'      => $i->nombre,
                        'descripcion' => $i->descripcion,
                        'precio'      => $i->precio,
                        'imagen'      => $this->imageUrl($i->imagen),
                    ])->all()
                : [];

            return $this->puntoVentaPayload($cliente) + ['menu' => $menu];
        });

        if ($data === null) {
            return response()->json(['message' => 'Punto de venta no encontrado.'], 404);
        }

        return response()->json($data);
    }

    /** Forma común de la ficha pública, para que listado y detalle no se desincronicen. */
    private function puntoVentaPayload(Customer $c): array
    {
        return [
            'id'          => $c->id,
            'slug'        => $c->slug,
            'nombre'      => $c->nombre_completo,
            'descripcion' => $c->descripcion_web,
            'horario'     => $c->horario,
            'logo'        => $this->imageUrl($c->logo),
            'banner'      => $this->imageUrl($c->banner),
            'direccion'   => $c->direccion,
            'telefono'    => $c->telefono,
            'latitud'     => $c->latitud,
            'longitud'    => $c->longitud,
            'menu_activo' => $c->menu_activo,
        ];
    }

    public function testimonials(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:testimonials", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);

            return CmsTestimonial::withoutGlobalScopes()
                ->select(['id', 'autor_nombre', 'autor_cargo', 'autor_empresa', 'autor_foto', 'contenido', 'estrellas'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($t) => [
                    'id'            => $t->id,
                    'autor_nombre'  => $t->autor_nombre,
                    'autor_cargo'   => $t->autor_cargo,
                    'autor_empresa' => $t->autor_empresa,
                    'autor_foto'    => $this->imageUrl($t->autor_foto),
                    'contenido'     => $t->contenido,
                    'estrellas'     => $t->estrellas,
                ])->all();
        });

        return response()->json($data);
    }

    public function faq(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:faq", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);

            return CmsFaq::withoutGlobalScopes()
                ->select(['id', 'pregunta', 'respuesta'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($f) => [
                    'id'        => $f->id,
                    'pregunta'  => $f->pregunta,
                    'respuesta' => $f->respuesta,
                ])->all();
        });

        return response()->json($data);
    }

    public function contact(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:contact", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);
            $contact = CmsContact::withoutGlobalScopes()
                ->select(['direccion', 'telefono', 'email', 'whatsapp', 'mapa_embed',
                          'facebook', 'instagram', 'linkedin', 'youtube', 'tiktok'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->first();

            if (! $contact) {
                return null;
            }

            return [
                'direccion'  => $contact->direccion,
                'telefono'   => $contact->telefono,
                'email'      => $contact->email,
                'whatsapp'   => $contact->whatsapp,
                'mapa_embed' => $contact->mapa_embed,
                'redes'      => array_filter([
                    'facebook'  => $contact->facebook,
                    'instagram' => $contact->instagram,
                    'linkedin'  => $contact->linkedin,
                    'youtube'   => $contact->youtube,
                    'tiktok'    => $contact->tiktok,
                ]),
            ];
        });

        return response()->json($data);
    }

    public function posts(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:posts", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);

            return CmsPost::withoutGlobalScopes()
                ->select(['id', 'titulo', 'slug', 'imagen', 'contenido', 'publicado_en'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->orderByDesc('publicado_en')
                ->get()
                ->map(fn ($p) => [
                    'id'           => $p->id,
                    'titulo'       => $p->titulo,
                    'slug'         => $p->slug,
                    'imagen'       => $this->imageUrl($p->imagen),
                    'publicado_en' => $p->publicado_en?->toISOString(),
                    'extracto'     => mb_substr(strip_tags($p->contenido), 0, 200) . '…',
                ])->all();
        });

        return response()->json($data);
    }

    public function post(string $slug, string $postSlug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:post:{$postSlug}", self::TTL, function () use ($slug, $postSlug) {
            $empresa = $this->empresa($slug);
            $post    = CmsPost::withoutGlobalScopes()
                ->select(['id', 'titulo', 'slug', 'contenido', 'imagen', 'publicado_en'])
                ->where('empresa_id', $empresa->id)
                ->where('slug', $postSlug)
                ->where('activo', true)
                ->firstOrFail();

            return [
                'id'           => $post->id,
                'titulo'       => $post->titulo,
                'slug'         => $post->slug,
                'contenido'    => $post->contenido,
                'imagen'       => $this->imageUrl($post->imagen),
                'publicado_en' => $post->publicado_en?->toISOString(),
            ];
        });

        return response()->json($data);
    }

    public function terminos(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:terminos", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);
            return $this->terminosData($empresa->id);
        });

        return response()->json($data);
    }

    /** Devuelve todas las secciones activas en una sola llamada. */
    public function all(string $slug): JsonResponse
    {
        $data = $this->cached("cms:{$slug}:all", self::TTL, function () use ($slug) {
            $empresa = $this->empresa($slug);
            $id      = $empresa->id;

            $hero    = CmsHero::withoutGlobalScopes()
                ->select(['titulo', 'subtitulo', 'descripcion', 'imagen', 'cta_texto', 'cta_url'])
                ->where('empresa_id', $id)->where('activo', true)->first();

            $about   = CmsAbout::withoutGlobalScopes()
                ->select(['titulo', 'descripcion', 'imagen', 'por_que_nosotros', 'numeros', 'caracteristicas'])
                ->where('empresa_id', $id)->where('activo', true)->first();

            $contact = CmsContact::withoutGlobalScopes()
                ->select(['direccion', 'telefono', 'email', 'whatsapp', 'mapa_embed',
                          'facebook', 'instagram', 'linkedin', 'youtube', 'tiktok'])
                ->where('empresa_id', $id)->where('activo', true)->first();

            return [
                'empresa' => [
                    'nombre' => $empresa->name,
                    'logo'   => $empresa->logo_path
                        ? Storage::disk('public')->url($empresa->logo_path)
                        : null,
                ],
                'hero' => $hero ? [
                    'titulo'      => $hero->titulo,
                    'subtitulo'   => $hero->subtitulo,
                    'descripcion' => $hero->descripcion,
                    'imagen'      => $this->imageUrl($hero->imagen),
                    'cta_texto'   => $hero->cta_texto,
                    'cta_url'     => $hero->cta_url,
                ] : null,
                'nosotros' => $about ? [
                    'titulo'           => $about->titulo,
                    'descripcion'      => $about->descripcion,
                    'imagen'           => $this->imageUrl($about->imagen),
                    'por_que_nosotros' => $about->por_que_nosotros ?? [],
                    'numeros'          => $about->numeros          ?? [],
                    'caracteristicas'  => $about->caracteristicas  ?? [],
                ] : null,
                'servicios'   => $this->buildServices($empresa),
                'productos'   => $this->buildProducts($empresa),
                'equipo'      => CmsTeamMember::withoutGlobalScopes()
                    ->select(['id', 'nombre', 'cargo', 'bio', 'foto'])
                    ->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')
                    ->get()->map(fn ($m) => ['id' => $m->id, 'nombre' => $m->nombre, 'cargo' => $m->cargo, 'bio' => $m->bio, 'foto' => $this->imageUrl($m->foto)])->all(),
                'clientes'    => CmsClientLogo::withoutGlobalScopes()
                    ->select(['id', 'nombre', 'logo', 'url'])
                    ->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')
                    ->get()->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'logo' => $this->imageUrl($c->logo), 'url' => $c->url])->all(),
                'testimonios' => CmsTestimonial::withoutGlobalScopes()
                    ->select(['id', 'autor_nombre', 'autor_cargo', 'autor_empresa', 'autor_foto', 'contenido', 'estrellas'])
                    ->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')
                    ->get()->map(fn ($t) => ['id' => $t->id, 'autor_nombre' => $t->autor_nombre, 'autor_cargo' => $t->autor_cargo, 'autor_empresa' => $t->autor_empresa, 'autor_foto' => $this->imageUrl($t->autor_foto), 'contenido' => $t->contenido, 'estrellas' => $t->estrellas])->all(),
                'faq'         => CmsFaq::withoutGlobalScopes()
                    ->select(['id', 'pregunta', 'respuesta'])
                    ->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')
                    ->get()->map(fn ($f) => ['id' => $f->id, 'pregunta' => $f->pregunta, 'respuesta' => $f->respuesta])->all(),
                'contacto'    => $contact ? [
                    'direccion'  => $contact->direccion,
                    'telefono'   => $contact->telefono,
                    'email'      => $contact->email,
                    'whatsapp'   => $contact->whatsapp,
                    'mapa_embed' => $contact->mapa_embed,
                    'redes'      => array_filter([
                        'facebook'  => $contact->facebook,
                        'instagram' => $contact->instagram,
                        'linkedin'  => $contact->linkedin,
                        'youtube'   => $contact->youtube,
                        'tiktok'    => $contact->tiktok,
                    ]),
                ] : null,
                'noticias'    => CmsPost::withoutGlobalScopes()
                    ->select(['id', 'slug', 'titulo', 'imagen', 'contenido', 'publicado_en'])
                    ->where('empresa_id', $id)->where('activo', true)->orderByDesc('publicado_en')->limit(10)
                    ->get()->map(fn ($p) => ['id' => $p->id, 'slug' => $p->slug, 'titulo' => $p->titulo, 'imagen' => $this->imageUrl($p->imagen), 'publicado_en' => $p->publicado_en?->toISOString(), 'extracto' => mb_substr(strip_tags($p->contenido), 0, 200) . '…'])->all(),
                'terminos'    => $this->terminosData($id),
            ];
        });

        return response()->json($data);
    }

    // ── Helpers privados ───────────────────────────────────────────────────

    private function buildServices(Empresa $empresa): array
    {
        $cms = CmsService::withoutGlobalScopes()
            ->select(['id', 'titulo', 'descripcion', 'caracteristicas', 'icono', 'imagen'])
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => [
                'id'              => $s->id,
                'source'          => 'cms',
                'titulo'          => $s->titulo,
                'descripcion'     => $s->descripcion,
                'caracteristicas' => $s->caracteristicas ?? [],
                'icono'           => $s->icono,
                'imagen'          => $this->imageUrl($s->imagen),
            ]);

        $designs = collect();
        if ($empresa->plan === 'enterprise') {
            $designs = ServiceDesign::withoutGlobalScopes()
                ->select(['id', 'nombre', 'descripcion_servicio', 'propuesta_valor', 'categoria'])
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->where('publicado_catalogo', true)
                ->with(['packages:id,service_design_id,nombre,descripcion,precio_estimado,base_cobro,unidad_cobro'])
                ->get()
                ->map(fn ($s) => [
                    'id'              => $s->id,
                    'source'          => 'design',
                    'titulo'          => $s->nombre,
                    'descripcion'     => $s->descripcion_servicio,
                    'propuesta_valor' => $s->propuesta_valor,
                    'categoria'       => $s->categoria,
                    'caracteristicas' => [],
                    'icono'           => null,
                    'imagen'          => null,
                    'paquetes'        => $s->packages->map(fn ($p) => array_filter([
                        'nombre'      => $p->nombre,
                        'descripcion' => $p->descripcion,
                        'precio'      => $p->precio_estimado ? (float) $p->precio_estimado : null,
                        'base_cobro'  => $p->base_cobro,
                        'unidad'      => $p->unidad_cobro,
                    ]))->values()->all(),
                ]);
        }

        return $cms->concat($designs)->values()->all();
    }

    private function buildProducts(Empresa $empresa): array
    {
        // Los productos vendibles viven en la tabla ÚNICA store_products.
        // El catálogo web solo muestra los publicados (misma verdad que api/store/products).
        return StoreProduct::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->with('storeCategory:id,nombre')
            ->orderBy('orden')
            ->get()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'source'          => 'store',
                'nombre'          => $p->nombre,
                'slug'            => $p->slug,
                'descripcion'     => $p->descripcion,
                'precio'          => $p->precio_venta !== null ? (float) $p->precio_venta : null,
                'unidad_precio'   => $p->unidad_precio,
                'categoria'       => $p->storeCategory?->nombre,
                'caracteristicas' => is_array($p->caracteristicas) ? $p->caracteristicas : [],
                'icono'           => null,
                'imagen'          => $this->imageUrl($p->imagen_principal),
                'presentaciones'  => [],
            ])
            ->values()
            ->all();
    }

    private function terminosData(int $empresaId): ?array
    {
        $t = CmsTerminos::withoutGlobalScopes()
            ->select(['titulo', 'contenido', 'ultima_actualizacion'])
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->first();

        if (! $t) {
            return null;
        }

        return [
            'titulo'               => $t->titulo,
            'contenido'            => $t->contenido,
            'ultima_actualizacion' => $t->ultima_actualizacion?->toDateString(),
        ];
    }
}
