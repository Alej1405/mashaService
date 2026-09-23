<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente del microservicio de declaraciones.
 *
 * El servicio vive aparte, en el VPS de microservicios. Si no responde, la
 * contabilidad del ERP sigue funcionando: solo no se puede generar el archivo,
 * y eso se dice en pantalla en vez de romper.
 */
class ServicioSri
{
    public function __construct(
        private readonly ?string $base = null,
        private readonly ?string $token = null,
    ) {}

    private function url(): string
    {
        return rtrim($this->base ?? config('services.sri.url', ''), '/');
    }

    public function configurado(): bool
    {
        return $this->url() !== '';
    }

    private function peticion()
    {
        $http = Http::timeout(20)->acceptJson();
        $token = $this->token ?? config('services.sri.token');

        return $token ? $http->withToken($token) : $http;
    }

    /** ¿Está arriba? Para pintarlo en la pantalla de declaraciones. */
    public function salud(): array
    {
        if (! $this->configurado()) {
            return ['arriba' => false, 'detalle' => 'El servicio no está configurado todavía.'];
        }

        try {
            $r = $this->peticion()->get($this->url() . '/salud');

            return $r->successful()
                ? ['arriba' => true, 'detalle' => 'versión ' . ($r->json('version') ?? '?')]
                : ['arriba' => false, 'detalle' => 'respondió ' . $r->status()];
        } catch (\Throwable $e) {
            return ['arriba' => false, 'detalle' => 'sin respuesta: ' . str($e->getMessage())->limit(80)];
        }
    }

    /**
     * @param  array<int, array{concepto: string, valor: string}>  $conceptos
     * @return array{ok: bool, xml?: string, error?: string}
     */
    public function formulario101(string $ruc, int $ejercicio, array $conceptos): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'error' => 'El servicio de declaraciones no está configurado.'];
        }

        try {
            $r = $this->peticion()->post($this->url() . '/formulario-101/xml', [
                'ruc' => $ruc, 'ejercicio' => $ejercicio, 'conceptos' => $conceptos,
            ]);

            return $r->successful()
                ? ['ok' => true, 'xml' => $r->body()]
                : ['ok' => false, 'error' => 'El servicio respondió ' . $r->status()];
        } catch (\Throwable $e) {
            Log::warning('Servicio SRI sin respuesta', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'El servicio no respondió. La contabilidad no se ve afectada.'];
        }
    }

    /** @param array<int, array{concepto: string, valor: string}> $conceptos */
    public function auditar101(string $ruc, int $ejercicio, array $conceptos): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'error' => 'El servicio de declaraciones no está configurado.'];
        }

        try {
            $r = $this->peticion()->post($this->url() . '/formulario-101/auditar', [
                'ruc' => $ruc, 'ejercicio' => $ejercicio, 'conceptos' => $conceptos,
            ]);

            return $r->successful()
                ? ['ok' => true] + $r->json()
                : ['ok' => false, 'error' => 'El servicio respondió ' . $r->status()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'El servicio no respondió.'];
        }
    }

    /**
     * Manda los casilleros ya calculados y recibe el XML del 104.
     *
     * El microservicio no consulta la base del ERP: recibe el dato hecho. Así
     * no hay dos sitios calculando el mismo impuesto.
     *
     * @param  array<string, float>  $casilleros
     * @return array{ok: bool, xml?: string, error?: string}
     */
    public function formulario104(string $ruc, int $anio, int $mes, array $casilleros): array
    {
        return $this->generar('/formulario-104/xml', [
            'ruc' => $ruc, 'anio' => $anio, 'mes' => $mes, 'casilleros' => $casilleros,
        ]);
    }

    /**
     * Manda el detalle del mes y recibe el XML del anexo transaccional.
     *
     * @return array{ok: bool, xml?: string, error?: string}
     */
    public function ats(array $datos): array
    {
        return $this->generar('/ats/xml', $datos);
    }

    /** Lo común de las dos: pedir el XML y no romper el ERP si no hay servicio. */
    private function generar(string $ruta, array $cuerpo): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'error' => 'El microservicio de declaraciones no está configurado.'];
        }

        try {
            // Un anexo de un mes cargado tarda más que una consulta normal.
            $r = Http::timeout(120)->acceptJson()
                ->withToken($this->token ?? config('services.sri.token'))
                ->post($this->url() . $ruta, $cuerpo);

            if (! $r->successful()) {
                Log::warning('microservicio SRI respondió ' . $r->status(), ['ruta' => $ruta]);

                return ['ok' => false, 'error' => 'El servicio respondió ' . $r->status() . '.'];
            }

            return ['ok' => true, 'xml' => $r->json('xml') ?? $r->body()];
        } catch (\Throwable $e) {
            Log::error('microservicio SRI sin respuesta', ['ruta' => $ruta, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Sin respuesta del servicio: ' . str($e->getMessage())->limit(120)];
        }
    }
}
