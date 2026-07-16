<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $empresa = app('store.empresa');

        $query = StoreProduct::withoutGlobalScopes()
            ->with(['storeCategory', 'imagenes'])
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true);

        if ($request->filled('category')) {
            $query->whereHas('storeCategory', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$search}%")
                ->orWhere('descripcion', 'like', "%{$search}%"));
        }

        if ($request->filled('minPrice')) {
            $query->where('precio_venta', '>=', $request->minPrice);
        }

        if ($request->filled('maxPrice')) {
            $query->where('precio_venta', '<=', $request->maxPrice);
        }

        match ($request->sort) {
            'precio_asc'  => $query->orderBy('precio_venta'),
            'precio_desc' => $query->orderByDesc('precio_venta'),
            'nombre'      => $query->orderBy('nombre'),
            default       => $query->orderBy('orden')->orderByDesc('created_at'),
        };

        $products = $query->paginate(24);

        return response()->json($products);
    }

    public function featured(Request $request): JsonResponse
    {
        $empresa = app('store.empresa');

        $products = StoreProduct::withoutGlobalScopes()
            ->with(['storeCategory', 'imagenes'])
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->where('destacado', true)
            ->orderBy('orden')
            ->limit(12)
            ->get();

        return response()->json($products);
    }

    /**
     * OJO con la firma: la URL es /ecommerce/{empresa_slug}/products/{slug} y Laravel
     * inyecta los parámetros de ruta POR POSICIÓN. Si no se declara $empresaSlug, el
     * primer hueco se lo come el slug de la empresa y $slug nunca recibe el del
     * producto (era un 404 permanente). La empresa real viene del middleware.
     */
    public function show(Request $request, string $empresaSlug, string $slug): JsonResponse
    {
        $empresa = app('store.empresa');

        $product = StoreProduct::withoutGlobalScopes()
            ->with(['storeCategory', 'imagenes'])
            ->where('empresa_id', $empresa->id)
            ->where('slug', $slug)
            ->where('publicado', true)
            ->firstOrFail();

        return response()->json($product);
    }

    /**
     * Mismo cuidado con la firma que en show(): sin $empresaSlug, aquí llegaba el slug
     * de la empresa contra un type-hint `int` y reventaba con TypeError (500).
     *
     * Se acepta id o slug: la URL dice {id}, pero los consumidores tienen el slug a mano
     * y da igual resolver por cualquiera de los dos.
     */
    public function related(Request $request, string $empresaSlug, string $idOSlug): JsonResponse
    {
        $empresa = app('store.empresa');

        $product = StoreProduct::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->when(
                ctype_digit($idOSlug),
                fn ($q) => $q->where('id', (int) $idOSlug),
                fn ($q) => $q->where('slug', $idOSlug),
            )
            ->firstOrFail();

        $related = StoreProduct::withoutGlobalScopes()
            ->with(['storeCategory', 'imagenes'])
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->where('id', '!=', $product->id)
            ->where('store_category_id', $product->store_category_id)
            ->limit(6)
            ->get();

        return response()->json($related);
    }
}
