<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreCoupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreCouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $empresa  = app('store.empresa');
        $subtotal = (float) ($request->subtotal ?? 0);

        $coupon = StoreCoupon::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('codigo', strtoupper($request->code))
            ->first();

        if (!$coupon || !$coupon->isValid($subtotal)) {
            return response()->json(['message' => 'Cupón inválido o expirado'], 422);
        }

        $descuento = $coupon->calcularDescuento($subtotal);

        return response()->json([
            'codigo'     => $coupon->codigo,
            'tipo'       => $coupon->tipo,
            'valor'      => $coupon->valor,
            'descuento'  => $descuento,
            'total'      => max(0, $subtotal - $descuento),
        ]);
    }
}
