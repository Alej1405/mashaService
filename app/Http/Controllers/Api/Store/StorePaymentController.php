<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorePaymentController extends Controller
{
    /**
     * Crea una intención de pago en el gateway configurado.
     * TODO: implementar cuando se defina el gateway (Stripe, Payphone, etc.)
     */
    public function intent(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Gateway de pagos pendiente de configuración'], 501);
    }

    /**
     * Callback del gateway de pagos.
     * TODO: implementar webhook según el gateway seleccionado.
     * Al recibir pago aprobado, llamar StoreOrderService::confirmOrder().
     */
    public function webhook(Request $request): JsonResponse
    {
        return response()->json(['received' => true]);
    }
}
