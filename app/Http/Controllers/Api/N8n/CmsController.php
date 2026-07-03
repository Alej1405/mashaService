<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\CmsFaq;
use App\Models\CmsPost;
use App\Models\CmsService;
use App\Models\Scopes\EmpresaScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gestión de contenido CMS desde n8n/Telegram. Toda consulta/creación se ancla
 * SIEMPRE a la empresa de la sesión (n8n_empresa), con withoutGlobalScope porque
 * la API n8n no tiene contexto de tenant Filament ni auth().
 */
class CmsController extends Controller
{
    /** Resumen para el menú del módulo. */
    public function resumen(Request $request): JsonResponse
    {
        $id = $this->empresaId($request);

        return response()->json([
            'ok' => true,
            'conteos' => [
                'posts' => $this->scope(CmsPost::query(), $id)->count(),
                'servicios' => $this->scope(CmsService::query(), $id)->count(),
                'faq' => $this->scope(CmsFaq::query(), $id)->count(),
            ],
        ]);
    }

    // ---- Publicaciones (posts) ---------------------------------------------

    public function postsIndex(Request $request): JsonResponse
    {
        $items = $this->scope(CmsPost::query(), $this->empresaId($request))
            ->latest('id')->limit(10)
            ->get(['id', 'titulo', 'activo', 'publicado_en']);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function postsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'contenido' => ['nullable', 'string'],
        ]);

        $empresaId = $this->empresaId($request);
        $post = new CmsPost();
        $post->empresa_id = $empresaId;
        $post->titulo = $data['titulo'];
        $post->slug = $this->slugUnico(CmsPost::class, $data['titulo'], $empresaId);
        $post->contenido = $data['contenido'] ?? null;
        $post->publicado_en = now();
        $post->activo = true;
        $post->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Publicación creada.',
            'item' => ['id' => $post->id, 'titulo' => $post->titulo, 'slug' => $post->slug],
        ], 201);
    }

    public function postsDestroy(Request $request, int $id): JsonResponse
    {
        return $this->eliminar(CmsPost::class, $this->empresaId($request), $id, 'Publicación');
    }

    // ---- Servicios ----------------------------------------------------------

    public function servicesIndex(Request $request): JsonResponse
    {
        $items = $this->scope(CmsService::query(), $this->empresaId($request))
            ->orderBy('sort_order')->limit(20)
            ->get(['id', 'titulo', 'activo']);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function servicesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $service = new CmsService();
        $service->empresa_id = $this->empresaId($request);
        $service->titulo = $data['titulo'];
        $service->descripcion = $data['descripcion'] ?? null;
        $service->activo = true;
        $service->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Servicio creado.',
            'item' => ['id' => $service->id, 'titulo' => $service->titulo],
        ], 201);
    }

    public function servicesDestroy(Request $request, int $id): JsonResponse
    {
        return $this->eliminar(CmsService::class, $this->empresaId($request), $id, 'Servicio');
    }

    // ---- FAQ ----------------------------------------------------------------

    public function faqIndex(Request $request): JsonResponse
    {
        $items = $this->scope(CmsFaq::query(), $this->empresaId($request))
            ->orderBy('sort_order')->limit(20)
            ->get(['id', 'pregunta', 'activo']);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function faqStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pregunta' => ['required', 'string', 'max:255'],
            'respuesta' => ['required', 'string'],
        ]);

        $faq = new CmsFaq();
        $faq->empresa_id = $this->empresaId($request);
        $faq->pregunta = $data['pregunta'];
        $faq->respuesta = $data['respuesta'];
        $faq->activo = true;
        $faq->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'FAQ creada.',
            'item' => ['id' => $faq->id, 'pregunta' => $faq->pregunta],
        ], 201);
    }

    public function faqDestroy(Request $request, int $id): JsonResponse
    {
        return $this->eliminar(CmsFaq::class, $this->empresaId($request), $id, 'FAQ');
    }

    // ---- Helpers ------------------------------------------------------------

    private function empresaId(Request $request): int
    {
        return (int) $request->attributes->get('n8n_empresa')->id;
    }

    /** Query anclada a la empresa, sin el global scope ambiguo. */
    private function scope(Builder $query, int $empresaId): Builder
    {
        return $query->withoutGlobalScope(EmpresaScope::class)->where('empresa_id', $empresaId);
    }

    private function eliminar(string $model, int $empresaId, int $id, string $etiqueta): JsonResponse
    {
        $item = $this->scope($model::query(), $empresaId)->find($id);

        if (! $item) {
            return response()->json([
                'ok' => false,
                'error' => 'no_encontrado',
                'mensaje' => $etiqueta . ' no encontrada en esta empresa.',
            ], 404);
        }

        $item->delete();

        return response()->json(['ok' => true, 'mensaje' => $etiqueta . ' eliminada.']);
    }

    private function slugUnico(string $model, string $titulo, int $empresaId): string
    {
        $base = Str::slug($titulo) ?: 'item';
        $slug = $base;
        $i = 2;
        while ($model::withoutGlobalScope(EmpresaScope::class)
            ->where('empresa_id', $empresaId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
