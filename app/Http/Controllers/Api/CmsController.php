<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsAbout;
use App\Models\CmsClientLogo;
use App\Models\CmsContact;
use App\Models\CmsFaq;
use App\Models\CmsHero;
use App\Models\CmsPost;
use App\Models\CmsProduct;
use App\Models\CmsService;
use App\Models\CmsTeamMember;
use App\Models\CmsTerminos;
use App\Models\CmsTestimonial;
use App\Models\Empresa;
use App\Models\ProductDesign;
use App\Models\ServiceDesign;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CmsController extends Controller
{
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

    // ── Endpoints ──────────────────────────────────────────────────────────

    public function hero(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $hero    = CmsHero::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->first();

        if (! $hero) {
            return response()->json(null);
        }

        return response()->json([
            'titulo'     => $hero->titulo,
            'subtitulo'  => $hero->subtitulo,
            'descripcion' => $hero->descripcion,
            'imagen'     => $this->imageUrl($hero->imagen),
            'cta_texto'  => $hero->cta_texto,
            'cta_url'    => $hero->cta_url,
        ]);
    }

    public function about(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $about   = CmsAbout::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->first();

        if (! $about) {
            return response()->json(null);
        }

        return response()->json([
            'titulo'           => $about->titulo,
            'descripcion'      => $about->descripcion,
            'imagen'           => $this->imageUrl($about->imagen),
            'por_que_nosotros' => $about->por_que_nosotros ?? [],
            'numeros'          => $about->numeros          ?? [],
            'caracteristicas'  => $about->caracteristicas  ?? [],
        ]);
    }

    public function services(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);

        // Servicios CMS estándar
        $cms = CmsService::withoutGlobalScopes()
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

        // ServiceDesign del plan enterprise
        $designs = collect();
        if ($empresa->plan === 'enterprise') {
            $designs = ServiceDesign::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->where('publicado_catalogo', true)
                ->get()
                ->map(function ($s) {
                    $paquetes = $s->packages->map(fn ($p) => array_filter([
                        'nombre'      => $p->nombre,
                        'descripcion' => $p->descripcion,
                        'precio'      => $p->precio_estimado ? (float) $p->precio_estimado : null,
                        'base_cobro'  => $p->base_cobro,
                        'unidad'      => $p->unidad_cobro,
                    ]));

                    return [
                        'id'              => $s->id,
                        'source'          => 'design',
                        'titulo'          => $s->nombre,
                        'descripcion'     => $s->descripcion_servicio,
                        'propuesta_valor' => $s->propuesta_valor,
                        'categoria'       => $s->categoria,
                        'caracteristicas' => [],
                        'icono'           => null,
                        'imagen'          => null,
                        'paquetes'        => $paquetes->values(),
                    ];
                });
        }

        return response()->json($cms->concat($designs)->values());
    }

    public function products(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);

        // Productos CMS estándar (todos los planes)
        $cms = CmsProduct::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'source'          => 'cms',
                'nombre'          => $p->nombre,
                'descripcion'     => $p->descripcion,
                'precio'          => $p->precio ? (float) $p->precio : null,
                'unidad_precio'   => $p->unidad_precio,
                'categoria'       => $p->categoria,
                'caracteristicas' => $p->caracteristicas ?? [],
                'icono'           => $p->icono,
                'imagen'          => $this->imageUrl($p->imagen),
            ]);

        // ProductDesign del plan enterprise
        $designs = collect();
        if ($empresa->plan === 'enterprise') {
            $designs = ProductDesign::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->where('publicado_catalogo', true)
                ->with(['presentations'])
                ->get()
                ->map(function ($p) {
                    $presentaciones = $p->presentations->map(fn ($pres) => array_filter([
                        'nombre' => $pres->nombre,
                        'precio' => $pres->pvp_estimado ? (float) $pres->pvp_estimado : null,
                        'margen' => $pres->margen_objetivo ? (float) $pres->margen_objetivo : null,
                    ]));

                    return [
                        'id'              => $p->id,
                        'source'          => 'design',
                        'nombre'          => $p->nombre,
                        'descripcion'     => $p->propuesta_valor,
                        'precio'          => null,
                        'unidad_precio'   => null,
                        'categoria'       => $p->categoria,
                        'caracteristicas' => [],
                        'icono'           => null,
                        'imagen'          => null,
                        'presentaciones'  => $presentaciones->values(),
                    ];
                });
        }

        return response()->json($cms->concat($designs)->values());
    }

    public function team(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $members = CmsTeamMember::withoutGlobalScopes()
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
            ]);

        return response()->json($members);
    }

    public function clients(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $clients = CmsClientLogo::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => [
                'id'     => $c->id,
                'nombre' => $c->nombre,
                'logo'   => $this->imageUrl($c->logo),
                'url'    => $c->url,
            ]);

        return response()->json($clients);
    }

    public function testimonials(string $slug): JsonResponse
    {
        $empresa      = $this->empresa($slug);
        $testimonials = CmsTestimonial::withoutGlobalScopes()
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
            ]);

        return response()->json($testimonials);
    }

    public function faq(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $faqs    = CmsFaq::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($f) => [
                'id'        => $f->id,
                'pregunta'  => $f->pregunta,
                'respuesta' => $f->respuesta,
            ]);

        return response()->json($faqs);
    }

    public function contact(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $contact = CmsContact::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->first();

        if (! $contact) {
            return response()->json(null);
        }

        return response()->json([
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
        ]);
    }

    public function posts(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $posts   = CmsPost::withoutGlobalScopes()
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
                'extracto'     => strip_tags(mb_substr($p->contenido, 0, 200)) . '…',
            ]);

        return response()->json($posts);
    }

    public function post(string $slug, string $postSlug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $post    = CmsPost::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('slug', $postSlug)
            ->where('activo', true)
            ->firstOrFail();

        return response()->json([
            'id'           => $post->id,
            'titulo'       => $post->titulo,
            'slug'         => $post->slug,
            'contenido'    => $post->contenido,
            'imagen'       => $this->imageUrl($post->imagen),
            'publicado_en' => $post->publicado_en?->toISOString(),
        ]);
    }

    /** Devuelve todas las secciones activas en una sola llamada. */
    public function all(string $slug): JsonResponse
    {
        $empresa = $this->empresa($slug);
        $id      = $empresa->id;

        $hero    = CmsHero::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->first();
        $about   = CmsAbout::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->first();
        $contact = CmsContact::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->first();

        return response()->json([
            'empresa' => [
                'nombre' => $empresa->name,
                'logo'   => $empresa->logo_path ? Storage::disk('public')->url($empresa->logo_path) : null,
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
            'servicios' => $this->services($slug)->getData(true),
            'productos' => $this->products($slug)->getData(true),
            'equipo' => CmsTeamMember::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')->get()->map(fn ($m) => ['nombre' => $m->nombre, 'cargo' => $m->cargo, 'bio' => $m->bio, 'foto' => $this->imageUrl($m->foto)]),
            'clientes' => CmsClientLogo::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')->get()->map(fn ($c) => ['nombre' => $c->nombre, 'logo' => $this->imageUrl($c->logo), 'url' => $c->url]),
            'testimonios' => CmsTestimonial::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')->get()->map(fn ($t) => ['autor_nombre' => $t->autor_nombre, 'autor_cargo' => $t->autor_cargo, 'autor_empresa' => $t->autor_empresa, 'autor_foto' => $this->imageUrl($t->autor_foto), 'contenido' => $t->contenido, 'estrellas' => $t->estrellas]),
            'faq' => CmsFaq::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->orderBy('sort_order')->get()->map(fn ($f) => ['pregunta' => $f->pregunta, 'respuesta' => $f->respuesta]),
            'contacto' => $contact ? ['direccion' => $contact->direccion, 'telefono' => $contact->telefono, 'email' => $contact->email, 'whatsapp' => $contact->whatsapp, 'mapa_embed' => $contact->mapa_embed, 'redes' => array_filter(['facebook' => $contact->facebook, 'instagram' => $contact->instagram, 'linkedin' => $contact->linkedin, 'youtube' => $contact->youtube, 'tiktok' => $contact->tiktok])] : null,
            'noticias' => CmsPost::withoutGlobalScopes()->where('empresa_id', $id)->where('activo', true)->orderByDesc('publicado_en')->limit(10)->get()->map(fn ($p) => ['slug' => $p->slug, 'titulo' => $p->titulo, 'imagen' => $this->imageUrl($p->imagen), 'publicado_en' => $p->publicado_en?->toISOString(), 'extracto' => strip_tags(mb_substr($p->contenido, 0, 200)) . '…']),
            'terminos' => $this->terminosData($id),
        ]);
    }

    public function terminos(string $slug): JsonResponse
    {
        $empresa  = $this->empresa($slug);
        $terminos = CmsTerminos::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->first();

        return response()->json($terminos ? $this->terminosData($empresa->id) : null);
    }

    private function terminosData(int $empresaId): ?array
    {
        $t = CmsTerminos::withoutGlobalScopes()
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
