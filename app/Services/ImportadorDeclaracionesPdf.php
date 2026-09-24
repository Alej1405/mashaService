<?php

namespace App\Services;

use App\Models\Declaracion;
use Illuminate\Support\Facades\DB;

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

            $ya = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
                ->where('anio', $cab['anio'])->where('mes', $cab['mes'])
                ->where(fn ($q) => $q->cargadas()->orWhereNotNull('presentado_en'))
                ->first();

            if ($ya) {
                $repetidas++;

                continue;
            }

            $resumen = $lectura['resumen'] ?? [];

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
            'repetidas'  => $repetidas,
            'rechazadas' => $rechazadas,
            'avisos'     => $avisos,
            'cadena'     => $this->verificarCadena($empresaId),
        ];
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
