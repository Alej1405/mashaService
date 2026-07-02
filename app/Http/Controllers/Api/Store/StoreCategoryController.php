<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $empresa = app('store.empresa');

        $categories = StoreCategory::withoutGlobalScopes()
            ->withCount(['products' => fn ($q) => $q->where('publicado', true)])
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('publicado', true)->withCount([
                'products' => fn ($q2) => $q2->where('publicado', true),
            ])])
            ->orderBy('orden')
            ->get();

        return response()->json($categories);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $empresa = app('store.empresa');

        $category = StoreCategory::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('slug', $slug)
            ->where('publicado', true)
            ->firstOrFail();

        $products = $category->products()
            ->withoutGlobalScopes()
            ->where('publicado', true)
            ->with('inventoryItem:id,stock_actual')
            ->orderBy('orden')
            ->paginate(24);

        return response()->json([
            'category' => $category,
            'products' => $products,
        ]);
    }

    /**
     * Datos completos para que el frontend genere la LANDING de una categoría:
     * cabecera (banner, contenido, SEO), breadcrumb, subcategorías y productos.
     * Endpoint aditivo — no altera index() ni show() (en uso).
     */
    public function landing(Request $request, string $slug): JsonResponse
    {
        $empresa = app('store.empresa');

        $category = StoreCategory::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('slug', $slug)
            ->where('publicado', true)
            ->firstOrFail();

        // Breadcrumb (de la raíz a la categoría actual).
        $breadcrumb = [];
        $cursor = $category;
        $guard = 0;
        while ($cursor && $guard++ < 10) {
            array_unshift($breadcrumb, ['nombre' => $cursor->nombre, 'slug' => $cursor->slug]);
            $cursor = $cursor->parent_id
                ? StoreCategory::withoutGlobalScopes()->find($cursor->parent_id)
                : null;
        }

        $subcategorias = StoreCategory::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('parent_id', $category->id)
            ->where('publicado', true)
            ->orderBy('orden')
            ->get()
            ->map(fn ($c) => [
                'nombre' => $c->nombre,
                'slug'   => $c->slug,
                'imagen' => $this->url($c->imagen),
            ])->all();

        $products = $category->products()
            ->withoutGlobalScopes()
            ->where('publicado', true)
            ->orderBy('orden')
            ->paginate(24);

        $products->getCollection()->transform(fn ($p) => [
            'id'           => $p->id,
            'nombre'       => $p->nombre,
            'slug'         => $p->slug,
            'precio_venta' => $p->precio_venta !== null ? (float) $p->precio_venta : null,
            'unidad_precio' => $p->unidad_precio,
            'imagen'       => $this->url($p->imagen_principal),
            'destacado'    => (bool) $p->destacado,
        ]);

        return response()->json([
            'category' => [
                'nombre'           => $category->nombre,
                'slug'             => $category->slug,
                'descripcion'      => $category->descripcion,
                'contenido'        => $category->contenido,
                'imagen'           => $this->url($category->imagen),
                'banner'           => $this->url($category->banner),
                'meta_titulo'      => $category->meta_titulo,
                'meta_descripcion' => $category->meta_descripcion,
            ],
            'breadcrumb'    => $breadcrumb,
            'subcategorias' => $subcategorias,
            'products'      => $products,
        ]);
    }

    /** Convierte una ruta de almacenamiento a URL pública (o null). */
    private function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
