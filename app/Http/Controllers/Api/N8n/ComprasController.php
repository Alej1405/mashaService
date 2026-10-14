<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\BuzonFacturas;
use App\Models\Purchase;
use App\Models\User;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Modules\Compras\Actions\NotificarCompra;
use App\Modules\Compras\Actions\RegistrarCompraElectronica;
use App\Services\ServicioSri;
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

    /**
     * Telegram: la foto de la factura (o su clave de acceso escrita) registra la compra en
     * la empresa activa de la sesión. Con la clave, el ERP pide al SRI el comprobante y
     * sigue el mismo camino que una factura por correo: proveedor, líneas, kardex y asiento.
     * Si falta la forma de pago, la respuesta trae los botones para elegirla.
     */
    public function foto(Request $request, ServicioSri $sri, RegistrarCompraElectronica $registrar, NotificarCompra $notificar): JsonResponse
    {
        $datos = $request->validate([
            'archivo' => ['nullable', 'required_without:clave', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'clave'   => ['nullable', 'required_without:archivo', 'string', 'max:80'],
            'chat_id' => ['nullable'],
        ]);
        $empresa = $request->attributes->get('n8n_empresa');

        $clave = preg_replace('/\D/', '', (string) ($datos['clave'] ?? ''));
        if ($clave === '') {
            $lectura = $sri->claveDesdeFoto((string) file_get_contents($request->file('archivo')->getRealPath()));
            if (! ($lectura['ok'] ?? false)) {
                return response()->json(['ok' => false, 'estado' => 'sin_clave', 'botones' => [],
                    'mensaje' => '📷 ' . ($lectura['error'] ?? 'No pude leer la clave de acceso en la foto.')]);
            }
            $clave = (string) $lectura['clave'];
        } elseif (strlen($clave) !== 49) {
            return response()->json(['ok' => false, 'estado' => 'sin_clave', 'botones' => [],
                'mensaje' => 'La clave de acceso tiene 49 dígitos y llegaron ' . strlen($clave) . '.']);
        }

        $r = $registrar->desdeClave($empresa, $clave, Purchase::ORIGEN_TELEGRAM,
            isset($datos['chat_id']) ? (string) $datos['chat_id'] : null);

        $compra = isset($r['compra_id']) ? Purchase::withoutGlobalScopes()->find($r['compra_id']) : null;
        $botones = $compra && ($r['estado'] ?? '') === 'pendiente' && $compra->requiere_forma_pago
            ? $notificar->opcionesDePago($compra) : [];

        return response()->json($r + ['botones' => $botones]);
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
