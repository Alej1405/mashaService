<?php

namespace App\Services;

use App\Models\FirmaElectronica;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * El certificado de firma electrónica de la empresa.
 *
 * En Ecuador lo emiten Security Data, Uanataca, el Banco Central y algunos
 * más, en formato PKCS#12 (.p12). Caduca a los dos años y sin él no se firma
 * ni un comprobante ni un anexo, así que lo que de verdad hace falta es que
 * alguien avise antes de que venza, no después.
 *
 * El archivo va a disco privado y la clave cifrada: en claro no se guarda.
 */
class FirmaElectronicaService
{
    /**
     * Lee el certificado sin depender de nada externo: openssl viene con PHP.
     *
     * @return array{ok: bool, datos?: array<string, mixed>, error?: string}
     */
    public function leer(string $rutaAbsoluta, string $clave): array
    {
        $contenido = @file_get_contents($rutaAbsoluta);

        if ($contenido === false) {
            return ['ok' => false, 'error' => 'No se pudo abrir el archivo.'];
        }

        $certificados = [];

        if (! @openssl_pkcs12_read($contenido, $certificados, $clave)) {
            // El error de openssl es críptico; la causa real casi siempre es una.
            return ['ok' => false, 'error' => 'La contraseña no abre el certificado, o el archivo no es un .p12 válido.'];
        }

        $datos = @openssl_x509_parse($certificados['cert'] ?? '');

        if (! $datos) {
            return ['ok' => false, 'error' => 'El certificado no se pudo interpretar.'];
        }

        return ['ok' => true, 'datos' => [
            'titular'        => $this->titular($datos),
            'identificacion' => $this->identificacion($datos),
            'emisor'         => $datos['issuer']['CN'] ?? ($datos['issuer']['O'] ?? null),
            'numero_serie'   => $datos['serialNumberHex'] ?? ($datos['serialNumber'] ?? null),
            'valido_desde'   => isset($datos['validFrom_time_t']) ? Carbon::createFromTimestamp($datos['validFrom_time_t']) : null,
            'valido_hasta'   => isset($datos['validTo_time_t']) ? Carbon::createFromTimestamp($datos['validTo_time_t']) : null,
        ]];
    }

    /**
     * Guarda el certificado. Si ya había uno, el anterior queda inactivo: la
     * empresa firma con uno solo, y así el historial no se pierde.
     */
    public function guardar(int $empresaId, UploadedFile $archivo, string $clave, ?int $usuarioId = null): array
    {
        $lectura = $this->leer($archivo->getRealPath(), $clave);

        if (! $lectura['ok']) {
            return $lectura;
        }

        $datos = $lectura['datos'];

        if ($datos['valido_hasta'] && $datos['valido_hasta']->isPast()) {
            return ['ok' => false, 'error' => 'Ese certificado ya caducó el '
                . $datos['valido_hasta']->format('d/m/Y') . '. Sube el vigente.'];
        }

        $ruta = $archivo->storeAs(
            "firmas/{$empresaId}",
            'certificado-' . now()->format('Ymd-His') . '.p12',
            'local',
        );

        FirmaElectronica::where('empresa_id', $empresaId)->update(['activa' => false]);

        $firma = FirmaElectronica::create([
            'empresa_id'     => $empresaId,
            'archivo'        => $ruta,
            'clave'          => $clave,
            'titular'        => $datos['titular'],
            'identificacion' => $datos['identificacion'],
            'emisor'         => $datos['emisor'],
            'numero_serie'   => $datos['numero_serie'],
            'valido_desde'   => $datos['valido_desde'],
            'valido_hasta'   => $datos['valido_hasta'],
            'activa'         => true,
            'cargada_por'    => $usuarioId,
        ]);

        return ['ok' => true, 'firma' => $firma];
    }

    public function vigente(int $empresaId): ?FirmaElectronica
    {
        return FirmaElectronica::where('empresa_id', $empresaId)
            ->where('activa', true)->latest('valido_hasta')->first();
    }

    /** El nombre del titular: unas emisoras lo ponen en CN y otras lo parten. */
    private function titular(array $datos): ?string
    {
        $sujeto = $datos['subject'] ?? [];

        if (! empty($sujeto['CN'])) {
            return is_array($sujeto['CN']) ? implode(' ', $sujeto['CN']) : $sujeto['CN'];
        }

        return trim(($sujeto['GN'] ?? '') . ' ' . ($sujeto['SN'] ?? '')) ?: null;
    }

    /** La cédula o el RUC del titular, según dónde lo ponga la emisora. */
    private function identificacion(array $datos): ?string
    {
        $sujeto = $datos['subject'] ?? [];

        foreach (['serialNumber', 'UID', 'OU'] as $campo) {
            $valor = $sujeto[$campo] ?? null;
            $valor = is_array($valor) ? implode(' ', $valor) : $valor;

            if ($valor && preg_match('/\d{10,13}/', (string) $valor, $coincide)) {
                return $coincide[0];
            }
        }

        return null;
    }
}
