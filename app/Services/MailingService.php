<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MailingService
{
    private string $apiKey;
    private string $domain;
    private string $fromEmail;
    private string $fromName;
    private string $baseUrl = 'https://api.mailgun.net/v3';

    public function __construct(Empresa $empresa)
    {
        $this->apiKey    = $empresa->mailgun_api_key ?? '';
        $this->domain    = $empresa->mailgun_domain ?? '';
        $this->fromEmail = $empresa->mailgun_from_email ?? '';
        $this->fromName  = $empresa->mailgun_from_name ?? $empresa->name;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->domain);
    }

    private function client()
    {
        return Http::withBasicAuth('api', $this->apiKey)
            ->timeout(10)
            ->acceptJson();
    }

    /**
     * Verifica que las credenciales sean válidas consultando el dominio configurado.
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales configuradas.'];
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/domains/{$this->domain}");

            if ($response->successful()) {
                $state = $response->json('domain.state', 'unknown');

                return [
                    'success' => true,
                    'message' => 'Conexión exitosa. Estado del dominio: ' . ucfirst($state),
                ];
            }

            return [
                'success' => false,
                'message' => match ($response->status()) {
                    401     => 'API Key inválida. Verifica las credenciales del servicio.',
                    404     => 'Dominio no encontrado. Verifica el nombre del dominio.',
                    default => "Error del servicio ({$response->status()}). Revisa las credenciales.",
                },
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Sin conexión: ' . $e->getMessage()];
        }
    }

    /**
     * Estadísticas totales de los últimos N días. Cachea 5 minutos.
     */
    public function getStats(int $days = 30): array
    {
        if (! $this->isConfigured()) {
            return $this->emptyStats();
        }

        $cacheKey = "mailing_stats_{$this->domain}_{$days}";

        return Cache::remember($cacheKey, 300, function () use ($days) {
            try {
                $response = $this->client()->get("{$this->baseUrl}/{$this->domain}/stats/total", [
                    'event'    => ['delivered', 'opened', 'clicked', 'bounced', 'complained', 'unsubscribed'],
                    'duration' => "{$days}d",
                ]);

                if (! $response->successful()) {
                    return $this->emptyStats();
                }

                $totals = $this->emptyStats();

                foreach ($response->json('stats', []) as $item) {
                    $totals['delivered']    += $item['delivered']['total'] ?? 0;
                    $totals['opened']       += $item['opened']['total'] ?? 0;
                    $totals['clicked']      += $item['clicked']['total'] ?? 0;
                    $totals['bounced']      += ($item['bounced']['permanent']['total'] ?? 0)
                                            + ($item['bounced']['temporary']['total'] ?? 0);
                    $totals['complained']   += $item['complained']['total'] ?? 0;
                    $totals['unsubscribed'] += $item['unsubscribed']['total'] ?? 0;
                }

                $base = max($totals['delivered'] + $totals['bounced'], 1);

                $totals['delivery_rate'] = round(($totals['delivered'] / $base) * 100, 1);
                $totals['open_rate']     = $totals['delivered'] > 0
                    ? round(($totals['opened'] / $totals['delivered']) * 100, 1)
                    : 0.0;
                $totals['click_rate']    = $totals['delivered'] > 0
                    ? round(($totals['clicked'] / $totals['delivered']) * 100, 1)
                    : 0.0;

                return $totals;
            } catch (\Exception) {
                return $this->emptyStats();
            }
        });
    }

    /**
     * Últimos N eventos del dominio. Cachea 2 minutos.
     */
    public function getEvents(int $limit = 20): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $cacheKey = "mailing_events_{$this->domain}_{$limit}";

        return Cache::remember($cacheKey, 120, function () use ($limit) {
            try {
                $response = $this->client()->get("{$this->baseUrl}/{$this->domain}/events", [
                    'limit' => $limit,
                ]);

                return $response->successful() ? $response->json('items', []) : [];
            } catch (\Exception) {
                return [];
            }
        });
    }

    /**
     * Envía un correo de prueba al email indicado.
     */
    public function sendTestEmail(string $to, string $toName = ''): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales configuradas.'];
        }

        $from = ! empty($this->fromEmail) ? $this->fromEmail : "noreply@{$this->domain}";
        $name = ! empty($this->fromName) ? $this->fromName : 'Mashaec ERP';

        try {
            $response = $this->client()
                ->asForm()
                ->post("{$this->baseUrl}/{$this->domain}/messages", [
                    'from'    => "{$name} <{$from}>",
                    'to'      => $toName ? "{$toName} <{$to}>" : $to,
                    'subject' => 'Correo de prueba — Mashaec ERP',
                    'text'    => 'Si recibes este correo, el servicio de correo está correctamente configurado en tu empresa Mashaec ERP.',
                    'html'    => '<p>Si recibes este correo, el <strong>servicio de correo está correctamente configurado</strong> en tu empresa <em>Mashaec ERP</em>. ✅</p>',
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Correo enviado a ' . $to];
            }

            $errMsg = $response->json('message') ?? "Error HTTP {$response->status()}";

            return ['success' => false, 'message' => "No se pudo enviar: {$errMsg}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Envía una plantilla de correo con variables de ejemplo sustituidas.
     */
    public function sendTemplateTest(string $to, MailTemplate $template): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales configuradas.'];
        }

        $from = ! empty($this->fromEmail) ? $this->fromEmail : "noreply@{$this->domain}";
        $name = ! empty($this->fromName) ? $this->fromName : 'Mashaec ERP';

        $sampleVars = [
            '{{nombre}}'   => 'Juan Pérez',
            '{{empresa}}'  => $name,
            '{{email}}'    => $to,
            '{{url}}'      => '#',
            '{{fecha}}'    => now()->format('d/m/Y'),
            '{{numero}}'   => 'TEST-001',
            '{{portal}}'   => '#',
        ];

        $html    = str_replace(array_keys($sampleVars), array_values($sampleVars), $template->toHtml());
        $subject = str_replace(array_keys($sampleVars), array_values($sampleVars), $template->subject);

        try {
            $response = $this->client()
                ->asForm()
                ->post("{$this->baseUrl}/{$this->domain}/messages", [
                    'from'    => "{$name} <{$from}>",
                    'to'      => $to,
                    'subject' => '[PRUEBA] ' . $subject,
                    'html'    => $html,
                    'text'    => strip_tags($html),
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Correo de prueba enviado a ' . $to];
            }

            $errMsg = $response->json('message') ?? "Error HTTP {$response->status()}";

            return ['success' => false, 'message' => "No se pudo enviar: {$errMsg}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function emptyStats(): array
    {
        return [
            'delivered'     => 0,
            'opened'        => 0,
            'clicked'       => 0,
            'bounced'       => 0,
            'complained'    => 0,
            'unsubscribed'  => 0,
            'delivery_rate' => 0.0,
            'open_rate'     => 0.0,
            'click_rate'    => 0.0,
        ];
    }
}
