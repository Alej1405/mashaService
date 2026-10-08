<?php

namespace App\Modules\Compras\Actions;

use App\Models\BuzonFacturas;
use App\Shared\Attributes\Documentado;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Deja en n8n el flujo que lee el buzón de facturas de una empresa: su credencial
 * IMAP y un flujo "correo → XML adjuntos → ERP". Lo arma el ERP para que sea
 * repetible: cambiar la contraseña o desactivar el buzón se refleja aquí.
 *
 * - No altera la bandeja: postProcessAction "nothing" (por defecto n8n marca leído).
 * - Criterio ALL + trackLastMessageId: lee lo que llegó desde que se activó, aunque
 *   alguien ya lo haya abierto (con UNSEEN se perdería esa factura). La primera
 *   lectura es SINCE la fecha de activación: no se traen facturas viejas.
 * - n8n solo transporta: validar y registrar la compra lo hace el ERP.
 */
#[Documentado(
    grupo: 'Compras',
    descripcion: 'Crea o actualiza en n8n la credencial IMAP y el flujo que lee el buzón de facturas de una empresa.',
    tipo: 'action',
)]
final class SincronizarBuzonN8n
{
    /** @return array{ok: bool, error?: string} */
    public function sincronizar(BuzonFacturas $buzon): array
    {
        if (! config('services.n8n.api_key')) {
            return $this->fallo($buzon, 'Falta la llave de la API de n8n en el servidor (N8N_API_KEY).');
        }

        try {
            if (! $buzon->activo) {
                if ($buzon->n8n_flujo_id) {
                    $this->api()->post("/workflows/{$buzon->n8n_flujo_id}/deactivate", new \stdClass)->throw();
                }

                return $this->exito($buzon, []);
            }

            $buzon->loadMissing('empresa');
            $anterior = $buzon->n8n_credencial_id;

            // La API no edita credenciales: se crea una nueva y se borra la vieja.
            $credencial = $this->api()->post('/credentials', [
                'name' => 'IMAP facturas · ' . $buzon->empresa->name,
                'type' => 'imap',
                'data' => [
                    'user'     => $buzon->usuario,
                    'password' => $buzon->password,
                    'host'     => $buzon->host,
                    'port'     => $buzon->puerto,
                    'secure'   => true,
                    'allowUnauthorizedCerts' => false,
                ],
            ])->throw()->json('id');

            $flujo = $this->flujo($buzon, $credencial);
            $id = $buzon->n8n_flujo_id;
            if ($id && $this->api()->get("/workflows/{$id}")->successful()) {
                $this->api()->put("/workflows/{$id}", $flujo)->throw();
            } else {
                $id = $this->api()->post('/workflows', $flujo)->throw()->json('id');
            }

            // Antes de activar: si la activación falla (clave IMAP mala), el próximo
            // guardado reutiliza este flujo en vez de dejar otro huérfano en n8n.
            $buzon->forceFill(['n8n_credencial_id' => $credencial, 'n8n_flujo_id' => $id])->saveQuietly();
            if ($anterior && $anterior !== $credencial) {
                $this->api()->delete("/credentials/{$anterior}");
            }

            // n8n exige un objeto como cuerpo; sin datos Laravel mandaría [].
            $this->api()->post("/workflows/{$id}/activate", new \stdClass)->throw();

            return $this->exito($buzon, []);
        } catch (\Throwable $e) {
            Log::warning('No se pudo sincronizar el buzón de facturas con n8n', ['buzon_id' => $buzon->id, 'error' => $e->getMessage()]);

            return $this->fallo($buzon, $this->explicar($e));
        }
    }

    /** Al quitar el buzón: el flujo y la credencial salen de n8n. */
    public function eliminar(BuzonFacturas $buzon): void
    {
        try {
            if ($buzon->n8n_flujo_id) {
                $this->api()->delete("/workflows/{$buzon->n8n_flujo_id}");
            }
            if ($buzon->n8n_credencial_id) {
                $this->api()->delete("/credentials/{$buzon->n8n_credencial_id}");
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo quitar de n8n el buzón de facturas', ['buzon_id' => $buzon->id, 'error' => $e->getMessage()]);
        }
    }

    private function flujo(BuzonFacturas $buzon, string $credencial): array
    {
        $correo = [
            'id' => (string) Str::uuid(), 'name' => 'Correo de facturas', 'type' => 'n8n-nodes-base.emailReadImap',
            // 2.1 y no 2: en la 2 n8n descarta el último UID y relee todo desde la activación.
            'typeVersion' => 2.1, 'position' => [0, 0],
            'parameters' => [
                'mailbox'             => 'INBOX',
                'postProcessAction'   => 'nothing',
                'downloadAttachments' => true,
                'format'              => 'simple',
                'options'             => ['customEmailConfig' => '["ALL"]', 'trackLastMessageId' => true],
            ],
            'credentials' => ['imap' => ['id' => $credencial, 'name' => 'IMAP facturas · ' . $buzon->empresa->name]],
        ];

        // Un ítem por XML adjunto, en base64: el SRI los emite en ISO-8859-1.
        $js = <<<JS
const salida = [];
for (let i = 0; i < \$input.all().length; i++) {
  const item = \$input.all()[i];
  for (const [clave, bin] of Object.entries(item.binary || {})) {
    const nombre = (bin.fileName || '').toLowerCase();
    if (!nombre.endsWith('.xml') && !(bin.mimeType || '').includes('xml')) continue;
    const buffer = await this.helpers.getBinaryDataBuffer(i, clave);
    salida.push({ json: { buzon_id: {$buzon->id}, archivo: bin.fileName || 'factura.xml', xml: buffer.toString('base64') } });
  }
}
return salida;
JS;
        $adjuntos = [
            'id' => (string) Str::uuid(), 'name' => 'XML adjuntos', 'type' => 'n8n-nodes-base.code',
            'typeVersion' => 2, 'position' => [260, 0], 'parameters' => ['jsCode' => $js],
        ];

        $erp = [
            'id' => (string) Str::uuid(), 'name' => 'Registrar en el ERP', 'type' => 'n8n-nodes-base.httpRequest',
            'typeVersion' => 4.2, 'position' => [520, 0],
            'parameters' => [
                'method'         => 'POST',
                'url'            => rtrim((string) config('services.n8n.erp_url'), '/') . '/api/n8n/v1/compras/recibir',
                'authentication' => 'genericCredentialType',
                'genericAuthType' => 'httpHeaderAuth',
                'sendHeaders'    => true,
                'headerParameters' => ['parameters' => [['name' => 'Accept', 'value' => 'application/json']]],
                'sendBody'       => true,
                'specifyBody'    => 'json',
                'jsonBody'       => '={{ JSON.stringify($json) }}',
                'options'        => ['timeout' => 60000, 'response' => ['response' => ['neverError' => true]]],
            ],
            'credentials' => ['httpHeaderAuth' => [
                'id'   => config('services.n8n.credencial_secreto'),
                'name' => 'ERP n8n Secret',
            ]],
        ];

        $enlace = fn (string $a) => ['main' => [[['node' => $a, 'type' => 'main', 'index' => 0]]]];

        return [
            'name'        => 'Masha Facturas · ' . $buzon->empresa->name,
            'nodes'       => [$correo, $adjuntos, $erp],
            'connections' => ['Correo de facturas' => $enlace('XML adjuntos'), 'XML adjuntos' => $enlace('Registrar en el ERP')],
            'settings'    => ['executionOrder' => 'v1'],
        ];
    }

    private function api(): PendingRequest
    {
        // Cloudflare bloquea los user-agent genéricos: se manda uno propio.
        return Http::baseUrl(rtrim((string) config('services.n8n.api_url'), '/'))
            ->withHeaders(['X-N8N-API-KEY' => config('services.n8n.api_key'), 'User-Agent' => 'mashacorp-erp/1.0'])
            ->acceptJson()->timeout(20);
    }

    private function explicar(\Throwable $e): string
    {
        $detalle = $e instanceof RequestException ? ($e->response->json('message') ?? $e->getMessage()) : $e->getMessage();

        if (Str::contains($detalle, ['Invalid credentials', 'AUTHENTICATIONFAILED', 'Authentication failed'])) {
            return 'El correo rechazó el usuario o la contraseña. Revisa que sea una contraseña de aplicación y que IMAP esté habilitado.';
        }
        if (Str::contains($detalle, ['ENOTFOUND', 'ECONNREFUSED', 'ETIMEDOUT'])) {
            return 'No se pudo llegar al servidor IMAP: revisa el servidor y el puerto.';
        }

        return 'n8n no aceptó la configuración: ' . Str::limit((string) $detalle, 200);
    }

    private function exito(BuzonFacturas $buzon, array $datos): array
    {
        $buzon->forceFill($datos + ['sincronizado_en' => now(), 'ultimo_error' => null])->saveQuietly();

        return ['ok' => true];
    }

    private function fallo(BuzonFacturas $buzon, string $error): array
    {
        $buzon->forceFill(['ultimo_error' => $error])->saveQuietly();

        return ['ok' => false, 'error' => $error];
    }
}
