<?php

namespace App\Services;

use App\Mail\EmpresaPlainMail;
use App\Models\Empresa;
use App\Models\EmpresaMailingStat;
use App\Models\MailCampaign;
use App\Models\MailingGroup;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class MailingService
{
    private Empresa $empresa;
    private string  $apiKey;
    private string  $domain;
    private string  $fromEmail;
    private string  $fromName;
    private ?string $logoUrl;
    private string  $baseUrl = 'https://api.mailgun.net/v3';

    public function __construct(Empresa $empresa)
    {
        $this->empresa   = $empresa;
        $this->apiKey    = $empresa->mailgun_api_key ?? '';
        $this->domain    = $empresa->mailgun_domain ?? '';
        $this->fromEmail = $empresa->mailgun_from_email ?? '';
        $this->fromName  = $empresa->mailgun_from_name ?? $empresa->name;
        $this->logoUrl   = $empresa->logo_path
            ? Storage::disk('public')->url($empresa->logo_path)
            : null;
    }

    /** Devuelve true si la empresa tiene SMTP configurado. */
    public function hasSmtp(): bool
    {
        return ! empty($this->empresa->smtp_host) && ! empty($this->empresa->smtp_username);
    }

    /** Verifica si el puerto SMTP es alcanzable (timeout 5s). */
    public function isSmtpPortReachable(): bool
    {
        $host    = $this->empresa->smtp_host;
        $port    = $this->empresa->smtp_port ?? 587;
        $enc     = $this->empresa->smtp_encryption ?? 'tls';
        $address = ($enc === 'ssl' ? 'ssl://' : '') . $host;
        $socket  = @fsockopen($address, $port, $errno, $errstr, 5);

        if ($socket) {
            fclose($socket);
            return true;
        }

        return false;
    }

    /**
     * Envía un correo HTML usando el SMTP configurado por la empresa.
     * Retorna array con 'success' y 'message'.
     */
    private function sendViaSMTP(string $to, string $toName, string $subject, string $html): array
    {
        $fromEmail = ! empty($this->empresa->smtp_from_email)
            ? $this->empresa->smtp_from_email
            : $this->empresa->smtp_username;

        $fromName = ! empty($this->empresa->smtp_from_name)
            ? $this->empresa->smtp_from_name
            : $this->empresa->name;

        config([
            'mail.mailers.empresa_smtp' => [
                'transport'  => 'smtp',
                'host'       => $this->empresa->smtp_host,
                'port'       => $this->empresa->smtp_port ?? 587,
                'encryption' => $this->empresa->smtp_encryption ?? 'tls',
                'username'   => $this->empresa->smtp_username,
                'password'   => $this->empresa->smtp_password,
            ],
        ]);

        try {
            Mail::mailer('empresa_smtp')
                ->to($toName ? "{$toName} <{$to}>" : $to)
                ->send(new EmpresaPlainMail($subject, $html, $fromEmail, $fromName));

            return ['success' => true, 'message' => 'Correo enviado a ' . $to];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error SMTP: ' . $e->getMessage()];
        }
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->domain);
    }

    private function client()
    {
        return Http::withBasicAuth('api', $this->apiKey)
            ->timeout(30)
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
     * Estadísticas totales reales.
     * Fuente primaria: API del servicio de correo (todos los métricas).
     * Fuente de respaldo: DB local (cuando la retención de la API haya expirado).
     * Cachea 5 minutos.
     */
    public function getStats(int $days = 30): array
    {
        $cacheKey = "mailing_stats_{$this->empresa->id}_{$days}";

        return Cache::remember($cacheKey, 300, function () use ($days) {
            // ── Fuente 1: API del servicio de correo ───────────────────────
            $apiTotals  = $this->fetchApiStats($days);
            $apiHasData = $this->statsHaveData($apiTotals);

            // Si la API devolvió datos reales, persistirlos en DB
            if ($apiHasData) {
                $this->saveStatsToDb($apiTotals);
            }

            // ── Fuente 2: DB local (respaldo cuando la API ya no tiene logs) ─
            $db = EmpresaMailingStat::where('empresa_id', $this->empresa->id)->first();

            $accepted     = $apiHasData ? $apiTotals['accepted']     : (int) ($db?->accepted     ?? 0);
            $delivered    = $apiHasData ? $apiTotals['delivered']    : (int) ($db?->delivered    ?? 0);
            $failed       = $apiHasData ? $apiTotals['failed']       : (int) ($db?->failed       ?? 0);
            $opened       = $apiHasData ? $apiTotals['opened']       : (int) ($db?->opened       ?? 0);
            $clicked      = $apiHasData ? $apiTotals['clicked']      : (int) ($db?->clicked      ?? 0);
            $bounced      = $apiHasData ? $apiTotals['bounced']      : (int) ($db?->bounced      ?? 0);
            $complained   = $apiHasData ? $apiTotals['complained']   : (int) ($db?->complained   ?? 0);
            $unsubscribed = $apiHasData ? $apiTotals['unsubscribed'] : (int) ($db?->unsubscribed ?? 0);

            $base = max($accepted, $delivered + $bounced + $failed, 1);

            return [
                'accepted'      => $accepted,
                'delivered'     => $delivered,
                'failed'        => $failed,
                'opened'        => $opened,
                'clicked'       => $clicked,
                'bounced'       => $bounced,
                'complained'    => $complained,
                'unsubscribed'  => $unsubscribed,
                'delivery_rate' => round(($delivered / $base) * 100, 1),
                'open_rate'     => $delivered > 0 ? round(($opened  / $delivered) * 100, 1) : 0.0,
                'click_rate'    => $delivered > 0 ? round(($clicked / $delivered) * 100, 1) : 0.0,
                'from_cache'    => ! $apiHasData && $db !== null,
                'last_synced'   => $db?->last_synced_at?->format('d/m/Y H:i'),
            ];
        });
    }

    /** Consulta la API del servicio de correo y devuelve totales crudos. */
    private function fetchApiStats(int $days): array
    {
        if (! $this->isConfigured()) {
            return $this->emptyApiStats();
        }

        try {
            // Eventos válidos para /stats/total: accepted, delivered, failed, opened, clicked, unsubscribed, complained
            // NOTA: 'bounced' NO es un evento válido aquí (causa 400). Los rebotes se obtienen de failed.permanent.bounce
            $response = $this->client()->get("{$this->baseUrl}/{$this->domain}/stats/total", [
                'event'    => ['accepted', 'delivered', 'failed', 'opened', 'clicked', 'unsubscribed', 'complained'],
                'duration' => "{$days}d",
            ]);

            if (! $response->successful()) {
                return $this->emptyApiStats();
            }

            $totals = $this->emptyApiStats();

            foreach ($response->json('stats', []) as $item) {
                $totals['accepted']     += $item['accepted']['total'] ?? 0;
                $totals['delivered']    += $item['delivered']['total'] ?? 0;
                $totals['failed']       += ($item['failed']['permanent']['total'] ?? 0)
                                        + ($item['failed']['temporary']['total'] ?? 0);
                $totals['opened']       += $item['opened']['total'] ?? 0;
                $totals['clicked']      += $item['clicked']['total'] ?? 0;
                // bounced se extrae de failed.permanent.bounce (no es un evento separado en /stats/total)
                $totals['bounced']      += $item['failed']['permanent']['bounce'] ?? 0;
                $totals['complained']   += $item['complained']['total'] ?? 0;
                $totals['unsubscribed'] += $item['unsubscribed']['total'] ?? 0;
            }

            return $totals;
        } catch (\Exception) {
            return $this->emptyApiStats();
        }
    }

    /** Persiste los stats en DB (upsert por empresa, guarda el máximo de cada métrica). */
    private function saveStatsToDb(array $api): void
    {
        try {
            $existing = EmpresaMailingStat::where('empresa_id', $this->empresa->id)->first();

            EmpresaMailingStat::updateOrCreate(
                ['empresa_id' => $this->empresa->id],
                [
                    // Siempre tomar el mayor entre lo que ya había y lo que la API devuelve.
                    // Así los valores nunca retroceden aunque la API devuelva un período más corto.
                    'accepted'       => max($api['accepted'],     (int) ($existing?->accepted     ?? 0)),
                    'delivered'      => max($api['delivered'],    (int) ($existing?->delivered    ?? 0)),
                    'failed'         => max($api['failed'],       (int) ($existing?->failed       ?? 0)),
                    'opened'         => max($api['opened'],       (int) ($existing?->opened       ?? 0)),
                    'clicked'        => max($api['clicked'],      (int) ($existing?->clicked      ?? 0)),
                    'bounced'        => max($api['bounced'],      (int) ($existing?->bounced      ?? 0)),
                    'complained'     => max($api['complained'],   (int) ($existing?->complained   ?? 0)),
                    'unsubscribed'   => max($api['unsubscribed'], (int) ($existing?->unsubscribed ?? 0)),
                    'last_synced_at' => now(),
                ]
            );
        } catch (\Exception) {
            // No bloquear el dashboard si falla la escritura
        }
    }

    private function statsHaveData(array $stats): bool
    {
        return ($stats['accepted'] + $stats['delivered'] + $stats['opened']
              + $stats['clicked']  + $stats['bounced']   + $stats['complained']
              + $stats['unsubscribed']) > 0;
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
     * Envía HTML arbitrario a un destinatario.
     * Usa SMTP si está configurado, de lo contrario usa Mailgun.
     */
    public function sendRawEmail(string $to, string $toName, string $subject, string $html): array
    {
        if ($this->hasSmtp() && $this->isSmtpPortReachable()) {
            return $this->sendViaSMTP($to, $toName, $subject, $html);
        }

        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales configuradas.'];
        }

        $from = ! empty($this->fromEmail) ? $this->fromEmail : "noreply@{$this->domain}";
        $name = ! empty($this->fromName) ? $this->fromName : $this->empresa->name;

        try {
            $response = $this->client()
                ->asForm()
                ->post("{$this->baseUrl}/{$this->domain}/messages", [
                    'from'    => "{$name} <{$from}>",
                    'to'      => $toName ? "{$toName} <{$to}>" : $to,
                    'subject' => $subject,
                    'html'    => $html,
                    'text'    => strip_tags($html),
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
     * Envía un correo de prueba al email indicado.
     * Usa SMTP si está configurado, de lo contrario usa Mailgun.
     */
    public function sendTestEmail(string $to, string $toName = ''): array
    {
        $logoImg = $this->logoUrl
            ? "<div style='text-align:center;margin-bottom:16px;'><img src='{$this->logoUrl}' alt='Logo' style='max-height:60px;max-width:200px;object-fit:contain;'></div>"
            : '';

        $html = $logoImg
            . '<p>Si recibes este correo, el <strong>servicio de correo está correctamente configurado</strong> en tu empresa <em>Mashaec ERP</em>. ✅</p>';

        if ($this->hasSmtp()) {
            return $this->sendViaSMTP($to, $toName, 'Correo de prueba — Mashaec ERP', $html);
        }

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
                    'html'    => $html,
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
     * Usa SMTP si está configurado, de lo contrario usa Mailgun.
     */
    public function sendTemplateTest(string $to, MailTemplate $template): array
    {
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

        $html    = str_replace(array_keys($sampleVars), array_values($sampleVars), $template->toHtml($this->logoUrl));
        $subject = '[PRUEBA] ' . str_replace(array_keys($sampleVars), array_values($sampleVars), $template->subject);

        if ($this->hasSmtp()) {
            return $this->sendViaSMTP($to, '', $subject, $html);
        }

        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales configuradas.'];
        }

        $from = ! empty($this->fromEmail) ? $this->fromEmail : "noreply@{$this->domain}";

        try {
            $response = $this->client()
                ->asForm()
                ->post("{$this->baseUrl}/{$this->domain}/messages", [
                    'from'    => "{$name} <{$from}>",
                    'to'      => $to,
                    'subject' => $subject,
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

    /**
     * Envía un correo a UN único destinatario personalizando las variables de la plantilla.
     * Usa SMTP si está configurado, de lo contrario usa Mailgun API.
     *
     * $contact = ['nombre' => '...', 'email' => '...']
     */
    public function sendSingleEmail(array $contact, MailTemplate $template): array
    {
        $email  = $contact['email'] ?? '';
        $nombre = ! empty($contact['nombre']) ? $contact['nombre'] : $email;

        if (empty($email)) {
            return ['success' => false, 'message' => 'Email vacío.', 'sent' => 0, 'failed' => 1];
        }

        $name = ! empty($this->fromName) ? $this->fromName : 'Mashaec ERP';

        $vars = [
            '{{nombre}}'  => $nombre,
            '{{empresa}}' => $name,
            '{{email}}'   => $email,
            '{{fecha}}'   => now()->format('d/m/Y'),
            '{{numero}}'  => '',
            '{{url}}'     => '',
            '{{portal}}'  => '',
        ];

        $html    = str_replace(array_keys($vars), array_values($vars), $template->toHtml($this->logoUrl));
        $subject = str_replace(array_keys($vars), array_values($vars), $template->subject);

        // ── Enviar por SMTP si está configurado ───────────────────────────
        if ($this->hasSmtp() && $this->isSmtpPortReachable()) {
            $result = $this->sendViaSMTP($email, $nombre, $subject, $html);
            return array_merge($result, ['sent' => $result['success'] ? 1 : 0, 'failed' => $result['success'] ? 0 : 1]);
        }

        // ── Enviar por Mailgun API ─────────────────────────────────────────
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales configuradas.', 'sent' => 0, 'failed' => 1];
        }

        $from = ! empty($this->fromEmail) ? $this->fromEmail : "noreply@{$this->domain}";

        try {
            $response = $this->client()
                ->asForm()
                ->post("{$this->baseUrl}/{$this->domain}/messages", [
                    'from'    => "{$name} <{$from}>",
                    'to'      => "{$nombre} <{$email}>",
                    'subject' => $subject,
                    'html'    => $html,
                    'text'    => strip_tags($html),
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => "Enviado a {$email}", 'sent' => 1, 'failed' => 0];
            }

            $errMsg = $response->json('message') ?? "Error HTTP {$response->status()}";
            return ['success' => false, 'message' => $errMsg, 'sent' => 0, 'failed' => 1];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'sent' => 0, 'failed' => 1];
        }
    }

    /**
     * Envía un correo masivo a múltiples destinatarios usando Mailgun batch sending.
     * Usa recipient-variables para personalización por destinatario.
     * Procesa en lotes de 1 000 contactos máximo (límite de Mailgun).
     *
     * $contacts = [['nombre' => '...', 'email' => '...'], ...]
     */
    public function sendMassEmail(array $contacts, MailTemplate $template): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales configuradas.', 'sent' => 0, 'failed' => 0];
        }

        if (empty($contacts)) {
            return ['success' => false, 'message' => 'No hay destinatarios.', 'sent' => 0, 'failed' => 0];
        }

        $from = ! empty($this->fromEmail) ? $this->fromEmail : "noreply@{$this->domain}";
        $name = ! empty($this->fromName) ? $this->fromName : 'Mashaec ERP';

        // Preparar el HTML reemplazando {{variable}} por %recipient.variable%
        $htmlTemplate = str_replace(
            ['{{nombre}}', '{{empresa}}', '{{email}}', '{{fecha}}', '{{numero}}', '{{url}}', '{{portal}}'],
            ['%recipient.nombre%', '%recipient.empresa%', '%recipient.email%', '%recipient.fecha%', '%recipient.numero%', '%recipient.url%', '%recipient.portal%'],
            $template->toHtml($this->logoUrl)
        );

        $subjectTemplate = str_replace(
            ['{{nombre}}', '{{empresa}}', '{{email}}'],
            ['%recipient.nombre%', '%recipient.empresa%', '%recipient.email%'],
            $template->subject
        );

        $sent   = 0;
        $failed = 0;
        $chunks = array_chunk($contacts, 1000);

        foreach ($chunks as $i => $chunk) {
            if ($i > 0) {
                usleep(500000); // 500 ms entre lotes cuando se llama directamente con >1000 contactos
            }
            $toList             = [];
            $recipientVariables = [];

            foreach ($chunk as $contact) {
                $email  = $contact['email'];
                $nombre = ! empty($contact['nombre']) ? $contact['nombre'] : $email;

                $toList[] = "{$nombre} <{$email}>";

                $recipientVariables[$email] = [
                    'nombre'  => $nombre,
                    'email'   => $email,
                    'empresa' => $name,
                    'fecha'   => now()->format('d/m/Y'),
                    'numero'  => '',
                    'url'     => '',
                    'portal'  => '',
                ];
            }

            try {
                $response = $this->client()
                    ->asForm()
                    ->post("{$this->baseUrl}/{$this->domain}/messages", [
                        'from'                 => "{$name} <{$from}>",
                        'to'                   => implode(',', $toList),
                        'subject'              => $subjectTemplate,
                        'html'                 => $htmlTemplate,
                        'text'                 => strip_tags($htmlTemplate),
                        'recipient-variables'  => json_encode($recipientVariables),
                    ]);

                if ($response->status() === 429) {
                    // Rate limit: esperar 60 segundos y reintentar una vez
                    sleep(60);
                    $retry = $this->client()
                        ->asForm()
                        ->post("{$this->baseUrl}/{$this->domain}/messages", [
                            'from'                 => "{$name} <{$from}>",
                            'to'                   => implode(',', $toList),
                            'subject'              => $subjectTemplate,
                            'html'                 => $htmlTemplate,
                            'text'                 => strip_tags($htmlTemplate),
                            'recipient-variables'  => json_encode($recipientVariables),
                        ]);
                    $retry->successful() ? $sent += count($chunk) : $failed += count($chunk);
                } elseif ($response->successful()) {
                    $sent += count($chunk);
                } else {
                    $failed += count($chunk);
                }
            } catch (\Exception) {
                $failed += count($chunk);
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "Enviados: {$sent}" . ($failed > 0 ? ", Fallidos: {$failed}" : ''),
            'sent'    => $sent,
            'failed'  => $failed,
        ];
    }

    /**
     * Envía HTML arbitrario de forma masiva a múltiples destinatarios via Mailgun.
     * $contacts = [['nombre' => '...', 'email' => '...'], ...]
     */
    public function sendRawMassEmail(array $contacts, string $subject, string $html): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'No hay credenciales del servicio de correo configuradas.', 'sent' => 0, 'failed' => 0];
        }

        if (empty($contacts)) {
            return ['success' => false, 'message' => 'No hay destinatarios.', 'sent' => 0, 'failed' => 0];
        }

        $from   = ! empty($this->fromEmail) ? $this->fromEmail : "noreply@{$this->domain}";
        $name   = ! empty($this->fromName)  ? $this->fromName  : $this->empresa->name;
        $sent   = 0;
        $failed = 0;

        foreach (array_chunk($contacts, 1000) as $chunk) {
            $toList             = [];
            $recipientVariables = [];

            foreach ($chunk as $contact) {
                $email  = $contact['email'];
                $nombre = ! empty($contact['nombre']) ? $contact['nombre'] : $email;
                $toList[]                  = "{$nombre} <{$email}>";
                $recipientVariables[$email] = ['nombre' => $nombre, 'email' => $email];
            }

            try {
                $response = $this->client()
                    ->asForm()
                    ->post("{$this->baseUrl}/{$this->domain}/messages", [
                        'from'                => "{$name} <{$from}>",
                        'to'                  => implode(',', $toList),
                        'subject'             => $subject,
                        'html'                => $html,
                        'text'                => strip_tags($html),
                        'recipient-variables' => json_encode($recipientVariables),
                    ]);

                $response->successful() ? $sent += count($chunk) : $failed += count($chunk);
            } catch (\Exception) {
                $failed += count($chunk);
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "Enviados: {$sent}" . ($failed > 0 ? ", Fallidos: {$failed}" : ''),
            'sent'    => $sent,
            'failed'  => $failed,
        ];
    }

    /**
     * Parsea un archivo CSV o Excel y devuelve un array de contactos.
     * Columnas esperadas: nombre, email, telefono, notas (en cualquier orden, insensible a mayúsculas).
     */
    public static function parseContactsFile(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match (true) {
            in_array($ext, ['csv', 'txt'])          => static::parseCsvFile($filePath),
            in_array($ext, ['xlsx', 'xls', 'ods'])  => static::parseSpreadsheetFile($filePath),
            default                                  => [],
        };
    }

    private static function parseCsvFile(string $filePath): array
    {
        $contacts = [];
        $handle   = fopen($filePath, 'r');

        if ($handle === false) {
            return [];
        }

        $headers = null;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($h) => strtolower(trim($h)), $row);
                continue;
            }

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            $data  = array_combine($headers, $row);
            $email = trim($data['email'] ?? '');

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contacts[] = [
                    'nombre'   => trim($data['nombre'] ?? $data['name'] ?? ''),
                    'email'    => $email,
                    'telefono' => trim($data['telefono'] ?? $data['phone'] ?? $data['teléfono'] ?? ''),
                    'notas'    => trim($data['notas'] ?? $data['notes'] ?? $data['nota'] ?? ''),
                ];
            }
        }

        fclose($handle);

        return $contacts;
    }

    private static function parseSpreadsheetFile(string $filePath): array
    {
        $contacts    = [];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            return [];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);

        foreach (array_slice($rows, 1) as $row) {
            $padded = array_pad($row, count($headers), '');
            $data   = array_combine($headers, $padded);
            $email  = trim($data['email'] ?? '');

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contacts[] = [
                    'nombre'   => trim($data['nombre'] ?? $data['name'] ?? ''),
                    'email'    => $email,
                    'telefono' => trim($data['telefono'] ?? $data['phone'] ?? $data['teléfono'] ?? ''),
                    'notas'    => trim($data['notas'] ?? $data['notes'] ?? $data['nota'] ?? ''),
                ];
            }
        }

        return $contacts;
    }

    /**
     * Devuelve la cuota de envíos del período actual para la empresa.
     * Fuente primaria: API del servicio de correo consultada con las fechas
     * exactas del período de facturación.
     * Fallback: valor guardado en empresa_mailing_stats.accepted.
     */
    public function getQuotaInfo(): array
    {
        $limit      = max(1, (int) ($this->empresa->mailing_monthly_limit ?? 3000));
        $billingDay = min(max((int) ($this->empresa->mailing_billing_day ?? 1), 1), 28);
        $today      = now();

        // ── Calcular el período de facturación actual ──────────────────────
        // Usamos '>' (estricto): si hoy ES el día de renovación, el período
        // actual es el que terminó HOY (mes anterior → hoy), no el que empieza hoy.
        if ($today->day > $billingDay) {
            $periodStart = $today->copy()->startOfMonth()->addDays($billingDay - 1);
        } else {
            $periodStart = $today->copy()->subMonth()->startOfMonth()->addDays($billingDay - 1);
        }
        $periodEnd = $periodStart->copy()->addMonth();

        // ── Consultar la API con las fechas exactas del período ────────────
        $cacheKey = "mailing_quota_{$this->empresa->id}_{$periodStart->toDateString()}";
        $sent = Cache::remember($cacheKey, 300, function () use ($periodStart, $periodEnd) {
            return $this->fetchAcceptedForPeriod($periodStart, $periodEnd);
        });

        // ── Fallback: DB persistida si la API no tiene logs ────────────────
        if ($sent === 0) {
            $db   = EmpresaMailingStat::where('empresa_id', $this->empresa->id)->first();
            $sent = (int) ($db?->accepted ?? 0);
        }

        $remaining  = max(0, $limit - $sent);
        $percentage = (int) round(($sent / $limit) * 100);

        $resetLabel = $periodEnd->isToday()
            ? 'hoy (' . $periodEnd->format('d/m/Y') . ')'
            : $periodEnd->format('d/m/Y');

        return [
            'limit'       => $limit,
            'sent'        => $sent,
            'remaining'   => $remaining,
            'percentage'  => min($percentage, 100),
            'reset_date'  => $periodEnd->format('d/m/Y'),
            'reset_label' => $resetLabel,
        ];
    }

    /**
     * Consulta la API para obtener el total de correos aceptados en un período exacto.
     */
    private function fetchAcceptedForPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/{$this->domain}/stats/total", [
                'event'      => ['accepted'],
                'start'      => $start->format('D, d M Y 00:00:00') . ' UTC',
                'end'        => $end->format('D, d M Y 00:00:00') . ' UTC',
                'resolution' => 'day',
            ]);

            if (! $response->successful()) {
                return 0;
            }

            $total = 0;
            foreach ($response->json('stats', []) as $item) {
                $total += $item['accepted']['total'] ?? 0;
            }

            return $total;
        } catch (\Exception) {
            return 0;
        }
    }

    private function emptyStats(): array
    {
        return [
            'accepted'      => 0,
            'delivered'     => 0,
            'failed'        => 0,
            'opened'        => 0,
            'clicked'       => 0,
            'bounced'       => 0,
            'complained'    => 0,
            'unsubscribed'  => 0,
            'delivery_rate' => 0.0,
            'open_rate'     => 0.0,
            'click_rate'    => 0.0,
            'from_cache'    => false,
            'last_synced'   => null,
        ];
    }

    private function emptyApiStats(): array
    {
        return [
            'accepted'     => 0,
            'delivered'    => 0,
            'failed'       => 0,
            'opened'       => 0,
            'clicked'      => 0,
            'bounced'      => 0,
            'complained'   => 0,
            'unsubscribed' => 0,
        ];
    }
}
