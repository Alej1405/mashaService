<?php

namespace App\Services;

use App\Models\Declaracion;
use Illuminate\Support\Facades\Storage;

/**
 * Da por presentado un 104 con el comprobante que entrega el SRI al declarar.
 *
 * El SRI no tiene un servicio que el ERP pueda consultar para validar una
 * declaración, así que la prueba es el propio comprobante: tiene que ser de esta
 * empresa, de este mes y del formulario 104, no estar usado en otra declaración,
 * y sus casillas tienen que ser las que el ERP generó. Un PDF de otro mes, de
 * otra empresa o con valores cambiados no pasa.
 *
 * Se guardan el serial y el código verificador (SRIDEC…): con ellos y el RUC,
 * cualquiera puede contrastarlo en el SRI.
 *
 * Marco: skill `declaraciones-sri-ec`, cumplir no es generar.
 */
class ComprobanteDeclaracionService
{
    /** Diferencia que se tolera por redondeo del portal. */
    private const TOLERANCIA = 0.01;

    public function __construct(private readonly ServicioSri $servicio)
    {
    }

    /**
     * @return array{ok: bool, error?: string, diferencias?: array<int, array{codigo: string, erp: float, sri: float}>}
     */
    public function registrar(Declaracion $d, string $pdf, string $nombre, ?int $usuarioId = null): array
    {
        if ($d->tipo !== 'f104' || ! $d->datos) {
            return ['ok' => false, 'error' => 'Esa declaración todavía no tiene valores generados.'];
        }

        $lectura = $this->servicio->leerDeclaracionPdf($pdf, $nombre);

        if (! ($lectura['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'No se pudo leer el PDF: ' . ($lectura['error'] ?? 'sin detalle') . '.'];
        }

        $cab = $lectura['cabecera'] ?? [];
        $rucEmpresa = preg_replace('/\D/', '', (string) $d->empresa?->numero_identificacion);
        $rucPdf = preg_replace('/\D/', '', (string) ($cab['ruc'] ?? ''));

        $error = match (true) {
            ($cab['tipo'] ?? 'otro') !== 'f104'
                => 'No es el comprobante de un 104: es un ' . ($cab['tipo_nombre'] ?? 'documento distinto') . '.',
            ! $rucPdf || $rucPdf !== $rucEmpresa
                => "Es del RUC {$rucPdf}, no de esta empresa.",
            (int) ($cab['anio'] ?? 0) !== $d->anio || (int) ($cab['mes'] ?? 0) !== $d->mes
                => 'Es del período ' . sprintf('%02d/%d', (int) ($cab['mes'] ?? 0), (int) ($cab['anio'] ?? 0))
                    . ", no de {$d->periodo}.",
            empty($cab['numero_serial'])
                => 'No trae número serial: no parece un comprobante emitido por el SRI.',
            default => null,
        };

        if ($error) {
            return ['ok' => false, 'error' => $error];
        }

        $usado = Declaracion::where('comprobante_presentacion', $cab['numero_serial'])
            ->where('id', '!=', $d->id)->exists();

        if ($usado) {
            return ['ok' => false, 'error' => "El serial {$cab['numero_serial']} ya respalda otra declaración."];
        }

        $diferencias = $this->diferencias($d->datos['casilleros'] ?? [], $lectura['casilleros'] ?? []);

        if ($diferencias) {
            return ['ok' => false, 'diferencias' => $diferencias,
                    'error' => 'Los valores del comprobante no son los que generó el ERP.'];
        }

        $archivo = sprintf('declaraciones/%d/comprobantes/f104-%d-%02d-%s.pdf',
            $d->empresa_id, $d->anio, $d->mes, $cab['numero_serial']);
        Storage::disk('local')->put($archivo, $pdf);

        $d->update([
            'presentado_en'            => $cab['fecha_recaudacion'] ?? now()->toDateString(),
            'comprobante_presentacion' => $cab['numero_serial'],
            'valor_pagado'             => $lectura['resumen']['total_pagado'] ?? $d->casillero('999'),
            'archivo_pdf'              => $archivo,
            'datos'                    => $d->datos + ['comprobante' => [
                'serial'             => $cab['numero_serial'],
                'codigo_verificador' => $cab['codigo_verificador'] ?? null,
                'fecha_recaudacion'  => $cab['fecha_recaudacion'] ?? null,
                'subido_por'         => $usuarioId,
                'subido_en'          => now()->toDateTimeString(),
            ]],
        ]);

        return ['ok' => true];
    }

    /**
     * Las casillas en que el comprobante y el ERP no dicen lo mismo.
     *
     * @param  array<string, mixed>  $erp
     * @param  array<string, mixed>  $sri
     * @return array<int, array{codigo: string, erp: float, sri: float}>
     */
    private function diferencias(array $erp, array $sri): array
    {
        $codigos = collect(array_keys($erp))->merge(array_keys($sri))->map(fn ($c) => (string) $c)->unique()->sort(SORT_NUMERIC);
        $salida = [];

        foreach ($codigos as $codigo) {
            $a = round((float) ($erp[$codigo] ?? 0), 4);
            $b = round((float) ($sri[$codigo] ?? 0), 4);

            // El factor de proporcionalidad lleva cuatro decimales; el resto, dos.
            $tolerancia = $codigo === '563' ? 0.0001 : self::TOLERANCIA;

            if (abs($a - $b) > $tolerancia) {
                $salida[] = ['codigo' => $codigo, 'erp' => $a, 'sri' => $b];
            }
        }

        return $salida;
    }
}
