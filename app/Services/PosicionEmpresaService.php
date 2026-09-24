<?php

namespace App\Services;

use App\Models\Declaracion;
use App\Models\Empresa;
use Illuminate\Support\Carbon;

/**
 * La posición de la empresa: lo que mira quien va a prestarle dinero.
 *
 * Un banco no pide "el ERP": pide las declaraciones de los últimos doce meses y
 * los balances presentados a la Superintendencia. De ahí saca tres cosas —si
 * factura, si declara a tiempo y si puede pagar— y con eso califica.
 *
 * Este servicio arma esa lectura desde lo que el sistema ya tiene, sin inventar
 * un indicador nuevo: los números salen del libro diario y de las declaraciones
 * presentadas, que son las dos fuentes que el banco va a contrastar.
 *
 * Marco: skills `contabilidad-ec` y `supercias-ec`.
 */
class PosicionEmpresaService
{
    public function __construct(
        private readonly ContabilidadService $contabilidad,
        private readonly GestionSriService $gestion,
    ) {
    }

    /**
     * @return array{ventas: array, tributaria: array, financiera: array,
     *               cumplimiento: array, señales: array<int, array>}
     */
    public function resumen(int $empresaId, ?int $anio = null): array
    {
        $anio ??= now()->year;
        $empresa = Empresa::withoutGlobalScopes()->findOrFail($empresaId);

        $ventas = $this->ventasDeclaradas($empresaId);
        $tributaria = $this->situacionTributaria($empresaId);
        $financiera = $this->indicadores($empresaId, $anio);
        $cumplimiento = $this->gestion->cumplimiento($empresaId);

        return [
            'empresa'      => $empresa,
            'ventas'       => $ventas,
            'tributaria'   => $tributaria,
            'financiera'   => $financiera,
            'cumplimiento' => $cumplimiento,
            'señales'      => $this->señales($ventas, $tributaria, $financiera, $cumplimiento),
        ];
    }

    /**
     * Lo declarado en los últimos doce meses, que es el periodo que mira un
     * banco. Sale de las declaraciones presentadas, no de lo que el ERP
     * calcula: el banco cruza contra el SRI, no contra nuestro sistema.
     */
    private function ventasDeclaradas(int $empresaId): array
    {
        $desde = now()->startOfMonth()->subMonths(12);

        $declaraciones = Declaracion::where('empresa_id', $empresaId)
            ->where('tipo', 'f104')
            ->whereNotNull('presentado_en')
            ->get()
            ->filter(fn ($d) => Carbon::create($d->anio, $d->mes, 1)->greaterThanOrEqualTo($desde));

        $meses = $declaraciones->map(fn ($d) => [
            'periodo' => sprintf('%02d/%d', $d->mes, $d->anio),
            'ventas'  => $d->casillero('419'),
            'compras' => $d->casillero('519'),
            'pagado'  => $d->casillero('999'),
        ])->sortBy('periodo')->values();

        $total = $meses->sum('ventas');

        return [
            'meses'          => $meses->all(),
            'total'          => round($total, 2),
            'promedio'       => $meses->count() ? round($total / $meses->count(), 2) : 0.0,
            'con_movimiento' => $meses->where('ventas', '>', 0)->count(),
            'declarados'     => $meses->count(),
        ];
    }

    /** Lo que el SRI ve: cuánto se declaró, cuánto se pagó y cuánto se arrastra. */
    private function situacionTributaria(int $empresaId): array
    {
        $ultima = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
            ->listas()->orderByDesc('anio')->orderByDesc('mes')->first();

        $presentadas = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
            ->whereNotNull('presentado_en')->get();

        return [
            'ultimo_periodo'    => $ultima ? sprintf('%02d/%d', $ultima->mes, $ultima->anio) : null,
            'credito_acumulado' => $ultima ? round($ultima->casillero('615') + $ultima->casillero('617'), 2) : 0.0,
            'iva_pagado_12m'    => round($presentadas->sum(fn ($d) => $d->casillero('999')), 2),
            'declaraciones'     => $presentadas->count(),
            'cargadas'          => $presentadas->where('origen', 'cargada')->count(),
        ];
    }

    /**
     * Los tres indicadores que se calculan de un balance y que el banco usa:
     * si puede pagar lo de este año, cuánto debe y cuánto le queda.
     */
    private function indicadores(int $empresaId, int $anio): array
    {
        $s = $this->contabilidad->saldos($empresaId, "{$anio}-12-31", $anio);
        $clasificacion = $this->contabilidad->clasificacion($empresaId);

        // Sin el desglose de corriente y no corriente, la liquidez se aproxima
        // con el total: se dice en pantalla para que nadie la tome por exacta.
        $liquidez = $s['pasivo'] > 0 ? round($s['activo'] / $s['pasivo'], 2) : null;
        $endeudamiento = $s['activo'] > 0 ? round($s['pasivo'] / $s['activo'] * 100, 1) : null;

        return [
            'activo'        => $s['activo'],
            'pasivo'        => $s['pasivo'],
            'patrimonio'    => $s['patrimonio'],
            'ingresos'      => $s['ingresos'],
            'resultado'     => $s['resultado'],
            'liquidez'      => $liquidez,
            'endeudamiento' => $endeudamiento,
            'clasificacion' => $clasificacion,
        ];
    }

    /**
     * Las señales, en el lenguaje de quien decide: qué juega a favor y qué en
     * contra. Cada una dice el porqué, no solo el veredicto.
     *
     * @return array<int, array{tono: string, titulo: string, detalle: string}>
     */
    private function señales(array $ventas, array $tributaria, array $financiera, array $cumplimiento): array
    {
        $señales = [];

        $señales[] = $cumplimiento['vencidos'] === 0
            ? ['tono' => 'ok', 'titulo' => 'Declaraciones al día',
               'detalle' => 'Ningún período vencido sin presentar. Es lo primero que revisa una entidad financiera.']
            : ['tono' => 'da', 'titulo' => $cumplimiento['vencidos'] . ' períodos vencidos',
               'detalle' => 'Un atraso en el IVA aparece en el buró tributario y frena cualquier crédito.'];

        $señales[] = $ventas['con_movimiento'] >= 6
            ? ['tono' => 'ok', 'titulo' => 'Facturación sostenida',
               'detalle' => $ventas['con_movimiento'] . ' de los últimos 12 meses con ventas declaradas.']
            : ['tono' => 'wa', 'titulo' => 'Facturación intermitente',
               'detalle' => 'Solo ' . $ventas['con_movimiento'] . ' de 12 meses con ventas. El banco busca continuidad, no monto.'];

        if ($financiera['endeudamiento'] !== null) {
            $señales[] = $financiera['endeudamiento'] <= 60
                ? ['tono' => 'ok', 'titulo' => 'Endeudamiento razonable',
                   'detalle' => $financiera['endeudamiento'] . ' % del activo está financiado con deuda.']
                : ['tono' => 'wa', 'titulo' => 'Endeudamiento alto',
                   'detalle' => $financiera['endeudamiento'] . ' % del activo es deuda. Por encima del 60 % encarece el crédito.'];
        }

        if ($tributaria['credito_acumulado'] > 0) {
            $señales[] = ['tono' => 'n', 'titulo' => 'Crédito tributario a favor',
                          'detalle' => 'Hay $ ' . number_format($tributaria['credito_acumulado'], 2, ',', '.')
                              . ' de IVA a favor acumulado. Es dinero que se recupera contra el IVA de las ventas.'];
        }

        if ($financiera['resultado'] < 0) {
            $señales[] = ['tono' => 'wa', 'titulo' => 'Resultado negativo en el ejercicio',
                          'detalle' => 'Pérdida de $ ' . number_format(abs($financiera['resultado']), 2, ',', '.')
                              . '. Normal en los primeros años, pero hay que poder explicarla.'];
        }

        return $señales;
    }
}
