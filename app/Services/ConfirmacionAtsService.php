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

    /**
     * Desde los pasos del ATS de un mes ya generado: la confirmación tiene que ser de ese mes.
     *
     * @return array{ok: bool, error?: string}
     */
    public function registrar(Declaracion $d, string $pdf, string $nombre, ?int $usuarioId = null): array
    {
        if ($d->tipo !== 'ats' || $d->estado !== 'listo') {
            return ['ok' => false, 'error' => 'Primero genera el anexo de ese mes.'];
        }

        $lectura = $this->leer($pdf, $nombre, $d->empresa_id);

        if (! $lectura['ok']) {
            return $lectura;
        }

        if ((int) $lectura['anio'] !== $d->anio || (int) $lectura['mes'] !== $d->mes) {
            return ['ok' => false, 'error' => 'La confirmación es de ' . sprintf('%02d/%d', (int) $lectura['mes'], (int) $lectura['anio'])
                . ", no de {$d->periodo}."];
        }

        $this->aplicar($d, $lectura, $pdf, $usuarioId);

        return ['ok' => true];
    }

    /**
     * Desde «Cargar confirmaciones del ATS»: el mes sale de la propia confirmación. Si el
     * ERP no generó ese ATS (meses de antes, o presentado por fuera), se crea el registro
     * con lo que dice el SRI: el mes queda constando como presentado.
     *
     * @return array{ok: bool, error?: string, periodo?: string}
     */
    public function registrarDesdePdf(int $empresaId, string $pdf, string $nombre, ?int $usuarioId = null): array
    {
        $lectura = $this->leer($pdf, $nombre, $empresaId);

        if (! $lectura['ok']) {
            return $lectura;
        }

        $d = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'ats')
            ->where('anio', (int) $lectura['anio'])->where('mes', (int) $lectura['mes'])->latest('id')->first()
            ?? Declaracion::create([
                'empresa_id' => $empresaId, 'tipo' => 'ats', 'origen' => 'cargada', 'estado' => 'listo',
                'anio' => (int) $lectura['anio'], 'mes' => (int) $lectura['mes'], 'solicitado_por' => $usuarioId,
            ]);

        $this->aplicar($d, $lectura, $pdf, $usuarioId);

        return ['ok' => true, 'periodo' => $d->periodo];
    }

    /** Lee la confirmación y comprueba que sea de esta empresa. */
    private function leer(string $pdf, string $nombre, int $empresaId): array
    {
        $lectura = $this->servicio->leerConfirmacionAts($pdf, $nombre);

        if (! ($lectura['ok'] ?? false)) {
            return ['ok' => false, 'error' => rtrim((string) ($lectura['error'] ?? 'No se pudo leer el PDF'), '. ') . '.'];
        }

        $rucEmpresa = preg_replace('/\D/', '', (string) \App\Models\Empresa::withoutGlobalScopes()->find($empresaId)?->numero_identificacion);

        if (($lectura['ruc'] ?? '') !== $rucEmpresa) {
            return ['ok' => false, 'error' => "La confirmación es del RUC {$lectura['ruc']}, no de esta empresa."];
        }

        return $lectura;
    }

    private function aplicar(Declaracion $d, array $lectura, string $pdf, ?int $usuarioId): void
    {
        $archivo = sprintf('declaraciones/%d/comprobantes/ats-%d-%02d-confirmacion.pdf', $d->empresa_id, $d->anio, $d->mes);
        Storage::disk('local')->put($archivo, $pdf);

        $d->update([
            'presentado_en' => substr((string) $lectura['presentado_en'], 0, 10),
            'archivo_pdf'   => $archivo,
            'datos'         => array_merge($d->datos ?? [], ['confirmacion' => [
                'presentado_en' => $lectura['presentado_en'],
                'subido_por'    => $usuarioId,
                'subido_en'     => now()->toDateTimeString(),
            ]]),
        ]);
    }
}
