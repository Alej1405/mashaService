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
            ->with(['storeCategory', 'inventoryItem:id,stock_actual'])
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
            ->with(['storeCategory', 'inventoryItem:id,stock_actual'])
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->where('destacado', true)
            ->orderBy('orden')
            ->limit(12)
            ->get();

        return response()->json($products);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $empresa = app('store.empresa');

        $product = StoreProduct::withoutGlobalScopes()
            ->with(['storeCategory', 'inventoryItem:id,stock_actual,nombre'])
            ->where('empresa_id', $empresa->id)
            ->where('slug', $slug)
            ->where('publicado', true)
            ->firstOrFail();

        return response()->json($product);
    }

    public function related(Request $request, int $id): JsonResponse
    {
        $empresa = app('store.empresa');

        $product = StoreProduct::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('id', $id)
            ->firstOrFail();

        $related = StoreProduct::withoutGlobalScopes()
            ->with(['storeCategory', 'inventoryItem:id,stock_actual'])
            ->where('empresa_id', $empresa->id)
            ->where('publicado', true)
            ->where('id', '!=', $id)
            ->where('store_category_id', $product->store_category_id)
            ->limit(6)
            ->get();

        return response()->json($related);
    }
}
