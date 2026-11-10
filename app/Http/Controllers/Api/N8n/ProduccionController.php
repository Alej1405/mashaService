<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Modules\Produccion\Actions\ConversacionProduccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** /produccion del bot: n8n manda el texto y el estado; el ERP responde. */
class ProduccionController extends Controller
{
    public function mensaje(Request $request, ConversacionProduccion $conversacion): JsonResponse
    {
        $datos = $request->validate([
            'text' => 'nullable|string|max:2000',
            'md'   => 'nullable|array',
        ]);

        return response()->json($conversacion->responder(
            $request->attributes->get('n8n_user'),
            $request->attributes->get('n8n_empresa'),
            (string) ($datos['text'] ?? ''),
            $datos['md'] ?? [],
        ));
    }
}
