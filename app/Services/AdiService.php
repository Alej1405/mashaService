<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

/**
 * Los datos del Anexo de Dividendos (ADI) de un ejercicio.
 *
 * Sale del libro: la utilidad del ejercicio es el resultado neto (707 del estado de
 * resultados de la Superintendencia, el mismo número); lo pendiente de ejercicios
 * anteriores es el saldo de resultados acumulados al inicio del año. Distribuir
 * dividendos es una decisión de la junta: mientras el ERP no la registre, lo
 * distribuido va en cero y se avisa. Nunca se infiere de un asiento.
 *
 * El ERP calcula; el microservicio escribe y valida el XML.
 * Marco: skill `declaraciones-sri-ec` (referencia ADI) y la ficha técnica ADI.
 */
class AdiService
{
    public function datos(int $empresaId, int $anio): array
    {
        $empresa = Empresa::withoutGlobalScopes()->findOrFail($empresaId);

        $resultado = (float) (collect(app(SuperciasService::class)->estado($empresaId, $anio, 'resultado_integral'))
            ->firstWhere('codigo', '707')['actual'] ?? 0);
        $utilidad = max(0, round($resultado, 2));

        // Resultados acumulados (3.6) al 31 de diciembre anterior: lo que quedó sin repartir.
        $pendiente = (float) DB::table('journal_entry_lines as l')
            ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('account_plans as a', 'a.id', '=', 'l.account_plan_id')
            ->where('j.empresa_id', $empresaId)->where('j.status', 'confirmado')
            ->where('a.code', 'like', '3.6%')->whereDate('j.fecha', '<', "{$anio}-01-01")
            ->sum(DB::raw('l.haber - l.debe'));

        $avisos = ['El ERP todavía no registra la junta que distribuye dividendos: lo distribuido va en cero. '
            . 'Si se repartieron utilidades, este anexo no está completo.'];
        if ($resultado < 0) {
            $avisos[] = 'El ejercicio cerró con pérdida (' . number_format($resultado, 2, ',', '.') . '): no hay utilidad que informar.';
        }

        return [
            'cabecera' => [
                'Anio'             => (string) $anio,
                'tipoInformante'   => '01',
                'tipoIdInformante' => 'R',
                'IdInformante'     => preg_replace('/\D/', '', (string) $empresa->numero_identificacion),
                'razonSocial'      => app(AtsService::class)->razonSocialDelSri($empresaId) ?? $empresa->name,
            ],
            'utilidades' => [
                'utilidadEjercicioInformado'             => $utilidad,
                'utilidadDistribuidaDistintaReinv'       => 0,
                'utilidadReinvertidaConDerechoReduccion' => 0,
                'utilidadReinvertidaSinDerechoReduccion' => 0,
                'utilidadPagadaAnticipado'               => 0,
                'utilidadNoDistribuidaEjerInfor'         => $utilidad,
                'utilidadNoDistribEjerAntPeriodoInfor'   => max(0, round($pendiente, 2)),
                'utilidadDistribEjerciciosAnteriores'    => 0,
            ],
            'avisos' => $avisos,
        ];
    }
}
