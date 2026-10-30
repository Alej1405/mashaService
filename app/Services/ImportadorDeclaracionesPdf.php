<?php

namespace App\Services;

use App\Models\Declaracion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Sube al histórico las declaraciones que la empresa ya presentó.
 *
 * Un contador llega con dos años de comprobantes en PDF: los sube todos de una
 * vez y de ahí sale el historial, con los 152 casilleros de cada mes y el
 * rastro del crédito tributario que va de uno al siguiente.
 *
 * Lo que entra por aquí **no cierra ningún período**: da trazabilidad y
 * posiciona a la empresa, pero el ERP no tiene los movimientos que la
 * sustentan. Cerrar sigue exigiendo una declaración generada por el sistema.
 *
 * Marco: skills `declaraciones-sri-ec` y `contabilidad-ec`.
 */
class ImportadorDeclaracionesPdf
{
    public function __construct(private readonly ServicioSri $servicio)
    {
    }

    /**
     * @param  array<int, array{contenido: string, nombre: string}>  $archivos
     * @return array{cargadas: int, repetidas: int, rechazadas: array, avisos: array, cadena: array}
     */
    public function importar(int $empresaId, array $archivos, ?int $usuarioId = null): array
    {
        $empresa = DB::table('empresas')->where('id', $empresaId)->first();
        $rucEmpresa = preg_replace('/\D/', '', (string) ($empresa->numero_identificacion ?? ''));

        $cargadas = 0;
        $repetidas = 0;
        $reemplazadas = [];
        $archivadas = [];
        $rechazadas = [];
        $avisos = [];

        foreach ($archivos as $archivo) {
            $lectura = $this->servicio->leerDeclaracionPdf($archivo['contenido'], $archivo['nombre']);

            if (! ($lectura['ok'] ?? false)) {
                $rechazadas[] = ['archivo' => $archivo['nombre'], 'motivo' => $lectura['error'] ?? 'no se pudo leer'];

                continue;
            }

            $cab = $lectura['cabecera'] ?? [];
            $rucPdf = preg_replace('/\D/', '', (string) ($cab['ruc'] ?? ''));

            // El PDF de otra empresa no entra: es el error más fácil de cometer
            // cuando se suben veinte archivos de golpe.
            if ($rucEmpresa && $rucPdf && $rucPdf !== $rucEmpresa) {
                $rechazadas[] = ['archivo' => $archivo['nombre'],
                                 'motivo' => "es del RUC {$rucPdf} (" . ($cab['razon_social'] ?? 'otra empresa') . ')'];

                continue;
            }

            if (($cab['tipo'] ?? 'otro') !== 'f104') {
                $rechazadas[] = ['archivo' => $archivo['nombre'],
                                 'motivo' => 'es un ' . ($cab['tipo_nombre'] ?? 'documento distinto') . ', no un 104'];

                continue;
            }

            if (empty($cab['anio']) || empty($cab['mes'])) {
                $rechazadas[] = ['archivo' => $archivo['nombre'], 'motivo' => 'no se pudo leer su período'];

                continue;
            }

            // Desde el inicio de gestión el 104 se declara en Cumplimiento, donde se
            // recalcula y se compara: un mes, un solo lugar.
            $inicio = $empresa->inicio_gestion_erp ?? null;
            if ($inicio && \Illuminate\Support\Carbon::create((int) $cab['anio'], (int) $cab['mes'], 1)
                    ->greaterThanOrEqualTo(\Illuminate\Support\Carbon::parse($inicio)->startOfMonth())) {
                $rechazadas[] = ['archivo' => $archivo['nombre'], 'motivo' => sprintf('es de %02d/%d, que ya se declara en '
                    . 'Cumplimiento: súbela ahí, en «Declara el IVA»', (int) $cab['mes'], (int) $cab['anio'])];

                continue;
            }

            $ya = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
                ->where('anio', $cab['anio'])->where('mes', $cab['mes'])
                ->where(fn ($q) => $q->cargadas()->orWhereNotNull('presentado_en'))
                ->first();

            $resumen = $lectura['resumen'] ?? [];

            // Un mes que se marcó presentado sin la declaración del SRI (el botón de
            // antes) tiene los valores que calculó el ERP, no los que constan en el
            // SRI. La declaración presentada manda: reemplaza sus casillas y su
            // crédito, que es lo que arrastra el mes siguiente.
            if ($ya && ! $ya->es_cargada && empty($ya->datos['comprobante'])) {
                $ya->update([
                    'archivo_pdf'              => $this->archivar($empresaId, $cab, $archivo['contenido']),
                    'presentado_en'            => $cab['fecha_recaudacion'] ?? $ya->presentado_en,
                    'comprobante_presentacion' => $cab['numero_serial'] ?? null,
                    'valor_pagado'             => $resumen['total_pagado'] ?? 0,
                    'datos' => ['casilleros' => $lectura['casilleros'] ?? [], 'cabecera' => $cab,
                        'comprobante' => [
                            'serial'             => $cab['numero_serial'] ?? null,
                            'codigo_verificador' => $cab['codigo_verificador'] ?? null,
                            'fecha_recaudacion'  => $cab['fecha_recaudacion'] ?? null,
                            'subido_por'         => $usuarioId,
                            'subido_en'          => now()->toDateTimeString(),
                        ],
                        'calculado_por_el_erp' => $ya->datos['casilleros'] ?? []],
                    'avisos' => ['Valores reemplazados por la declaración presentada en el SRI: lo que había '
                        . 'calculado el ERP queda guardado aparte para compararlo.'],
                ]);
                $reemplazadas[] = $ya->periodo;

                continue;
            }

            if ($ya) {
                // Ya estaba, pero sin su constancia: se archiva el PDF, que es la prueba de lo presentado.
                if (! $ya->archivo_pdf && in_array($ya->comprobante_presentacion, [null, $cab['numero_serial'] ?? null], true)) {
                    $ya->update(['archivo_pdf' => $this->archivar($empresaId, $cab, $archivo['contenido'])]);
                    $archivadas[] = $ya->periodo;
                } else {
                    $repetidas++;
                }

                continue;
            }

            Declaracion::create([
                'empresa_id'    => $empresaId,
                'tipo'          => 'f104',
                'origen'        => 'cargada',
                'anio'          => (int) $cab['anio'],
                'mes'           => (int) $cab['mes'],
                'estado'        => 'listo',
                'generado_en'   => $cab['fecha_recaudacion'] ?? null,
                'presentado_en' => $cab['fecha_recaudacion'] ?? null,
                'comprobante_presentacion' => $cab['numero_serial'] ?? null,
                'archivo_pdf'   => $this->archivar($empresaId, $cab, $archivo['contenido']),
                'valor_pagado'  => $resumen['total_pagado'] ?? 0,
                'solicitado_por' => $usuarioId,
                // Los 152 casilleros, no un resumen: de ahí sale todo lo demás.
                'datos' => ['casilleros' => $lectura['casilleros'] ?? [], 'cabecera' => $cab],
                'avisos' => array_merge(
                    ['Cargada del comprobante en PDF del portal. No cierra el período: el ERP no '
                     . 'tiene los movimientos que la respaldan.'],
                    $lectura['avisos'] ?? [],
                ),
            ]);

            $cargadas++;
        }

        return [
            'cargadas'   => $cargadas,
            'archivadas' => $archivadas,
            'repetidas'  => $repetidas,
            'reemplazadas' => $reemplazadas,
            'rechazadas' => $rechazadas,
            'avisos'     => $avisos,
            'cadena'     => $this->verificarCadena($empresaId),
        ];
    }

    /** Guarda el PDF de la declaración en el mismo sitio que usa Cumplimiento. */
    private function archivar(int $empresaId, array $cab, string $contenido): string
    {
        $ruta = sprintf('declaraciones/%d/comprobantes/f104-%d-%02d-%s.pdf',
            $empresaId, $cab['anio'], $cab['mes'], $cab['numero_serial'] ?? 'sin-serial');
        Storage::disk('local')->put($ruta, $contenido);

        return $ruta;
    }

    /**
     * El crédito tributario tiene que encadenar: lo que sale de un mes entra
     * en el siguiente. Si no, falta una declaración por cargar.
     */
    public function verificarCadena(int $empresaId): array
    {
        $periodos = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
            ->whereNotNull('presentado_en')
            ->orderBy('anio')->orderBy('mes')
            ->get()
            ->map(fn ($d) => [
                'anio' => $d->anio, 'mes' => $d->mes,
                'casilleros' => $d->datos['casilleros'] ?? [],
            ])->all();

        return $this->servicio->verificarCadena($periodos);
    }
}
