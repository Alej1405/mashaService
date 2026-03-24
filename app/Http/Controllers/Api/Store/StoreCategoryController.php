<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function show(Request $request, string $cat_slug): JsonResponse
    {
        $empresa = app('store.empresa');

        $category = StoreCategory::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('slug', $cat_slug)
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
}
