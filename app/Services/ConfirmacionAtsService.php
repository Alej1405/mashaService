<?php

namespace App\Services;

use App\Models\Declaracion;
use Illuminate\Support\Facades\Storage;

/**
 * Da por presentado un ATS con la «Confirmación de Presentación de Archivo» del portal.
 *
 * Es la misma auditoría que el 104: lo que cuenta es el documento del SRI, no que
 * alguien diga que lo subió. La confirmación tiene que ser de esta empresa y de este
 * mes. Que el portal la haya recibido no es que la haya aceptado: el SRI valida en 48
 * horas, y eso queda dicho junto al estado.
 *
 * Marco: skill `declaraciones-sri-ec`, cumplir no es generar.
 */
class ConfirmacionAtsService
{
    public function __construct(private readonly ServicioSri $servicio)
    {
    }

    /** @return array{ok: bool, error?: string} */
    public function registrar(Declaracion $d, string $pdf, string $nombre, ?int $usuarioId = null): array
    {
        if ($d->tipo !== 'ats' || $d->estado !== 'listo') {
            return ['ok' => false, 'error' => 'Primero genera el anexo de ese mes.'];
        }

        $lectura = $this->servicio->leerConfirmacionAts($pdf, $nombre);

        if (! ($lectura['ok'] ?? false)) {
            return ['ok' => false, 'error' => rtrim((string) ($lectura['error'] ?? 'No se pudo leer el PDF'), '. ') . '.'];
        }

        $rucEmpresa = preg_replace('/\D/', '', (string) $d->empresa?->numero_identificacion);

        $error = match (true) {
            ($lectura['ruc'] ?? '') !== $rucEmpresa
                => "La confirmación es del RUC {$lectura['ruc']}, no de esta empresa.",
            (int) ($lectura['anio'] ?? 0) !== $d->anio || (int) ($lectura['mes'] ?? 0) !== $d->mes
                => 'La confirmación es de ' . sprintf('%02d/%d', (int) $lectura['mes'], (int) $lectura['anio'])
                    . ", no de {$d->periodo}.",
            default => null,
        };

        if ($error) {
            return ['ok' => false, 'error' => $error];
        }

        $archivo = sprintf('declaraciones/%d/comprobantes/ats-%d-%02d-confirmacion.pdf', $d->empresa_id, $d->anio, $d->mes);
        Storage::disk('local')->put($archivo, $pdf);

        $d->update([
            'presentado_en' => substr((string) $lectura['presentado_en'], 0, 10),
            'archivo_pdf'   => $archivo,
            'datos'         => ($d->datos ?? []) + ['confirmacion' => [
                'presentado_en' => $lectura['presentado_en'],
                'subido_por'    => $usuarioId,
                'subido_en'     => now()->toDateTimeString(),
            ]],
        ]);

        return ['ok' => true];
    }
}
