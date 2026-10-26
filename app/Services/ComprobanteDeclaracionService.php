<?php

namespace App\Services;

use App\Models\Declaracion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Da por presentado un 104 con la declaración que consta en el SRI.
 *
 * Lo presentado ya es un hecho: no se bloquea. El flujo es leer la declaración,
 * volver a calcular ese mes con lo registrado y comparar casilla por casilla. Si
 * algo difiere, manda el SRI: sus valores quedan como los de la declaración y lo
 * que calculó el ERP se guarda aparte. Una diferencia es una falla nuestra (de
 * cálculo o de clasificación), así que avisa a soporte en la campana.
 *
 * Solo se rechaza lo que no es auténtico: otra empresa, otro mes, otro
 * formulario, sin serial o con un serial ya usado.
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
        if ($d->tipo !== 'f104') {
            return ['ok' => false, 'error' => 'Solo las declaraciones del IVA (104) se registran así.'];
        }

        $lectura = $this->servicio->leerDeclaracionPdf($pdf, $nombre);

        if (! ($lectura['ok'] ?? false)) {
            // El «Comprobante electrónico para pago» trae el texto codificado y no tiene
            // casillas: es el error más probable, así que se dice qué subir en su lugar.
            $esComprobanteDePago = str_contains((string) ($lectura['error'] ?? ''), 'glifos');

            return ['ok' => false, 'error' => $esComprobanteDePago
                ? 'Ese PDF parece el «Comprobante electrónico para pago», que no trae las casillas. Sube la '
                    . 'declaración: en SRI en línea → Declaraciones presentadas, descarga la del mes (el PDF de varias páginas).'
                : 'No se pudo leer el PDF: ' . rtrim($lectura['error'] ?? 'sin detalle', '. ') . '.'];
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

        // Se vuelve a calcular el mes con lo que hay registrado hoy, y con eso se compara.
        $calculo = app(Formulario104Service::class)->calcular($d->empresa_id, $d->anio, $d->mes);
        $sri = array_map(fn ($v) => round((float) $v, 4), $lectura['casilleros'] ?? []);
        $diferencias = $this->diferencias($calculo['casilleros'], $sri);

        $archivo = sprintf('declaraciones/%d/comprobantes/f104-%d-%02d-%s.pdf',
            $d->empresa_id, $d->anio, $d->mes, $cab['numero_serial']);
        Storage::disk('local')->put($archivo, $pdf);

        $d->update([
            'presentado_en'            => $cab['fecha_recaudacion'] ?? now()->toDateString(),
            'comprobante_presentacion' => $cab['numero_serial'],
            'valor_pagado'             => $lectura['resumen']['total_pagado'] ?? $sri['999'] ?? 0,
            'archivo_pdf'              => $archivo,
            'estado'                   => 'listo',
            'generado_en'              => $d->generado_en ?? now(),
            'datos'                    => [
                // Manda lo presentado; lo que el formulario no trae se queda con el cálculo.
                'casilleros'           => array_replace($calculo['casilleros'], $sri),
                'calculado_por_el_erp' => $calculo['casilleros'],
                'diferencias'          => $diferencias,
                'comprobante'          => [
                    'serial'             => $cab['numero_serial'],
                    'codigo_verificador' => $cab['codigo_verificador'] ?? null,
                    'fecha_recaudacion'  => $cab['fecha_recaudacion'] ?? null,
                    'subido_por'         => $usuarioId,
                    'subido_en'          => now()->toDateTimeString(),
                ],
            ],
            'avisos' => $diferencias
                ? ['El cálculo del ERP difería del SRI en ' . count($diferencias) . ' casillas; quedó lo presentado.']
                : [],
        ]);

        if ($diferencias) {
            $this->avisarFalla($d, $diferencias);
        }

        return ['ok' => true, 'diferencias' => $diferencias];
    }

    /**
     * Una diferencia con el SRI es una falla del ERP: llega a soporte para corregir el
     * cálculo o la clasificación, y queda en el log.
     *
     * @param  array<int, array{codigo: string, erp: float, sri: float}>  $diferencias
     */
    private function avisarFalla(Declaracion $d, array $diferencias): void
    {
        $detalle = collect($diferencias)->take(8)
            ->map(fn ($x) => "{$x['codigo']}: ERP {$x['erp']} · SRI {$x['sri']}")->join(' · ');

        Log::warning('104: el cálculo del ERP difiere de lo presentado en el SRI', [
            'empresa_id' => $d->empresa_id, 'periodo' => $d->periodo, 'diferencias' => $diferencias,
        ]);

        \Filament\Notifications\Notification::make()
            ->title("Falla del 104 · {$d->empresa?->name} · {$d->periodo}")
            ->body('El ERP calculó distinto que la declaración presentada en ' . count($diferencias) . " casillas. {$detalle}")
            ->danger()
            ->sendToDatabase(\App\Models\User::role('super_admin')->get());
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
        // Solo las casillas que trae la declaración del SRI: el formulario vigente ya no
        // tiene algunas que el ERP calcula (los conteos 111, 113, 115…), y una casilla
        // ausente no es un cero que contradiga al ERP.
        $codigos = collect(array_keys($sri))->map(fn ($c) => (string) $c)->unique()->sort(SORT_NUMERIC);
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
