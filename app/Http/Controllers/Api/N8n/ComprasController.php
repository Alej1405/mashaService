<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\BuzonFacturas;
use App\Models\Purchase;
use App\Models\User;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Modules\Compras\Actions\NotificarCompra;
use App\Modules\Compras\Actions\RegistrarCompraElectronica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Compras que llegan por n8n. Sin sesión de Telegram: el flujo de correo corre
 * solo, y la respuesta a un botón llega con el chat de quien lo pulsó. Las dos
 * rutas van detrás de N8nGate (secreto compartido); la empresa sale del buzón o
 * de la compra, y quien responde tiene que tener acceso a esa empresa.
 */
class ComprasController extends Controller
{
    /** El flujo de correo de una empresa entrega el XML de una factura. */
    public function recibir(Request $request, RegistrarCompraElectronica $registrar): JsonResponse
    {
        $datos = $request->validate([
            'buzon_id' => ['required', 'integer'],
            'xml'      => ['required', 'string', 'max:2000000'],   // base64
            'archivo'  => ['nullable', 'string', 'max:255'],
        ]);

        $buzon = BuzonFacturas::withoutGlobalScopes()->with('empresa')->where('activo', true)->find($datos['buzon_id']);
        if (! $buzon || ! $buzon->empresa) {
            return response()->json(['ok' => false, 'estado' => 'rechazada', 'mensaje' => 'Buzón no registrado o desactivado.'], 404);
        }

        $xml = base64_decode($datos['xml'], true);
        if ($xml === false) {
            return response()->json(['ok' => false, 'estado' => 'rechazada', 'mensaje' => 'El adjunto no viene en base64.'], 422);
        }

        return response()->json($registrar->desdeXml($buzon->empresa, $xml));
    }

    /** Respuesta a "¿cómo se pagó?" desde un botón de Telegram. */
    public function formaPago(Request $request, ConfirmarCompraElectronica $confirmar, NotificarCompra $notificar): JsonResponse
    {
        $datos = $request->validate([
            'chat_id'   => ['required'],
            'compra_id' => ['required', 'integer'],
            'forma'     => ['required', 'string'],
            'medio_id'  => ['nullable', 'integer'],
        ]);

        $compra = Purchase::withoutGlobalScopes()->with('empresa')->find($datos['compra_id']);
        if (! $compra || ! $this->puedeResponder((string) $datos['chat_id'], $compra)) {
            return response()->json(['ok' => false, 'mensaje' => 'No tienes acceso a esa compra.'], 403);
        }
        if ($compra->status !== Purchase::BORRADOR) {
            return response()->json(['ok' => true, 'mensaje' => $notificar->resumen($compra)]);
        }

        try {
            $confirmar->formaPago($compra, $datos['forma'], ($datos['medio_id'] ?? 0) ?: null);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'mensaje' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['ok' => true, 'mensaje' => $notificar->resumen($compra->refresh())]);
    }

    private function puedeResponder(string $chatId, Purchase $compra): bool
    {
        $usuarios = User::where('telegram_chat_id', $chatId)->get()
            ->merge(User::whereIn('id', \App\Models\TelegramSession::where('chat_id', $chatId)->pluck('user_id'))->get());

        return $usuarios->contains(fn (User $u) => $u->empresa_id === $compra->empresa_id
            || $compra->empresa?->usuariosAcceso()->whereKey($u->id)->exists()
            || $u->hasRole('super_admin'));
    }
}
