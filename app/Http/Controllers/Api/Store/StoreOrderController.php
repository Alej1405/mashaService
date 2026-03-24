<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Services\StoreOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreOrderController extends Controller
{
    public function __construct(private StoreOrderService $orderService) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.store_product_id'  => 'required|integer',
            'items.*.cantidad'          => 'required|numeric|min:1',
            'shipping_address'          => 'required|array',
            'shipping_address.linea1'   => 'required|string',
            'shipping_address.ciudad'   => 'required|string',
            'coupon_code'               => 'nullable|string',
            'notas'                     => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->orderService->createOrder(
                empresa:         app('store.empresa'),
                customer:        $request->user(),
                items:           $request->items,
                shippingAddress: $request->shipping_address,
                couponCode:      $request->coupon_code,
                notes:           $request->notas,
            );

            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->withoutGlobalScopes()
            ->with('orderItems')
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $request->user()
            ->orders()
            ->withoutGlobalScopes()
            ->with(['orderItems.product', 'coupon'])
            ->findOrFail($id);

        return response()->json($order);
    }
}
