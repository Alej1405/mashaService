<?php

namespace App\Services;

use App\Models\ComprobanteSri;
use App\Models\Declaracion;
use App\Models\Empresa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * El calendario de lo que la empresa debe y de lo que ya entregó.
 *
 * Decir "estamos al día con el SRI y con la Superintendencia" exige dos cosas
 * que antes no existían: saber **desde cuándo** el ERP responde por esta
 * empresa, y tener la huella de cada informe —generado, descargado,
 * presentado—. Un informe generado y nunca descargado no es cumplimiento; es
 * un archivo en un disco.
 *
 * Marco: skills `declaraciones-sri-ec` y `supercias-ec`.
 */
class GestionSriService
{
    /** Día de vencimiento del 104 según el noveno dígito del RUC. */
    private const VENCIMIENTO_POR_DIGITO = [
        1 => 10, 2 => 12, 3 => 14, 4 => 16, 5 => 18,
        6 => 20, 7 => 22, 8 => 24, 9 => 26, 0 => 28,
    ];

    /**
     * Los períodos por los que esta empresa responde, del más reciente al más
     * antiguo, con su estado real.
     *
     * @return array<int, array<string, mixed>>
     */
    public function periodos(int $empresaId, int $limite = 12): array
    {
        $empresa = Empresa::withoutGlobalScopes()->findOrFail($empresaId);
        $inicio = $this->inicioDeGestion($empresa);

        if (! $inicio) {
            return [];
        }

        $declaraciones = Declaracion::where('empresa_id', $empresaId)
            ->where('tipo', 'f104')
            ->get()
            ->keyBy(fn ($d) => sprintf('%d-%02d', $d->anio, $d->mes));

        // El mes en curso todavía no se declara: se cierra y luego se presenta.
        $mes = now()->startOfMonth()->subMonth();
        $periodos = [];

        while ($mes->greaterThanOrEqualTo($inicio) && count($periodos) < $limite) {
            $clave = $mes->format('Y-m');
            $declaracion = $declaraciones->get($mes->format('Y-m'))
                ?? $declaraciones->get(sprintf('%d-%02d', $mes->year, $mes->month));

            $periodos[] = [
                'anio'        => $mes->year,
                'mes'         => $mes->month,
                'etiqueta'    => $mes->translatedFormat('F \d\e Y'),
                'vence'       => $this->vencimiento($empresa, $mes->year, $mes->month),
                'movimiento'  => $this->hayMovimiento($empresaId, $mes->year, $mes->month),
                'declaracion' => $declaracion,
                'estado'      => $this->estado($declaracion, $this->vencimiento($empresa, $mes->year, $mes->month)),
            ];

            $mes->subMonth();
        }

        return $periodos;
    }

    /**
     * Desde cuándo responde el ERP. Si nadie lo fijó, se toma el primer mes
     * con movimiento: es la respuesta honesta, no una fecha inventada.
     */
    public function inicioDeGestion(Empresa $empresa): ?Carbon
    {
        if ($empresa->inicio_gestion_erp) {
            return Carbon::parse($empresa->inicio_gestion_erp)->startOfMonth();
        }

        $primera = DB::table('journal_entries')->where('empresa_id', $empresa->id)
            ->where('status', 'confirmado')->min('fecha');

        return $primera ? Carbon::parse($primera)->startOfMonth() : null;
    }

    /** El noveno dígito del RUC manda sobre el calendario. */
    public function vencimiento(Empresa $empresa, int $anio, int $mes): ?Carbon
    {
        $ruc = preg_replace('/\D/', '', (string) ($empresa->numero_identificacion ?? ''));

        if (strlen($ruc) < 9) {
            return null;
        }

        $dia = self::VENCIMIENTO_POR_DIGITO[(int) $ruc[8]] ?? 28;

        // El 104 de un mes vence al mes siguiente.
        return Carbon::create($anio, $mes, 1)->addMonth()->day($dia)->endOfDay();
    }

    /** ¿Hubo algo que declarar? Un mes en cero también se declara, pero se avisa. */
    public function hayMovimiento(int $empresaId, int $anio, int $mes): array
    {
        $desde = Carbon::create($anio, $mes, 1)->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();

        $ventas = DB::table('sales')->where('empresa_id', $empresaId)
            ->where('estado', 'confirmado')->whereBetween('fecha', [$desde, $hasta])->count();

        $compras = DB::table('purchases')->where('empresa_id', $empresaId)
            ->where('status', 'confirmado')->whereBetween('date', [$desde, $hasta])->count()
            + DB::table('gastos')->where('empresa_id', $empresaId)
                ->where('estado', 'confirmado')->whereBetween('fecha', [$desde, $hasta])->count();

        $importados = ComprobanteSri::where('empresa_id', $empresaId)
            ->delPeriodo($anio, $mes)->sinConciliar()->count();

        return [
            'ventas'     => $ventas,
            'compras'    => $compras,
            'importados' => $importados,
            'vacio'      => $ventas === 0 && $compras === 0 && $importados === 0,
        ];
    }

    /**
     * El estado de un período. Generado no es presentado, y descargado no es
     * presentado tampoco: son tres cosas distintas y el panel las distingue.
     */
    private function estado(?Declaracion $d, ?Carbon $vence): array
    {
        $vencido = $vence && now()->greaterThan($vence);

        return match (true) {
            $d?->presentado_en !== null => ['clave' => 'presentado', 'tono' => 'ok',
                                            'texto' => 'Presentado el ' . $d->presentado_en->format('d/m/Y')],
            $d?->descargado_en !== null => ['clave' => 'descargado', 'tono' => 'wa',
                                            'texto' => 'Descargado, falta presentar'],
            $d?->estado === 'listo'     => ['clave' => 'generado', 'tono' => 'wa',
                                            'texto' => 'Generado, sin descargar'],
            $d?->estado === 'error'     => ['clave' => 'error', 'tono' => 'da', 'texto' => 'Falló al generar'],
            $d !== null                 => ['clave' => 'en_cola', 'tono' => 'n', 'texto' => 'En cola'],
            $vencido                    => ['clave' => 'vencido', 'tono' => 'da', 'texto' => 'Vencido, sin generar'],
            default                     => ['clave' => 'pendiente', 'tono' => 'n', 'texto' => 'Pendiente'],
        };
    }

    /**
     * El resumen que responde de verdad a "¿estamos cumpliendo?".
     *
     * @return array{desde: string|null, total: int, presentados: int, pendientes: int,
     *               vencidos: int, al_dia: bool, sin_movimiento: int}
     */
    public function cumplimiento(int $empresaId): array
    {
        $periodos = $this->periodos($empresaId, 36);
        $cuenta = fn (string $clave) => count(array_filter($periodos, fn ($p) => $p['estado']['clave'] === $clave));

        $vencidos = $cuenta('vencido');
        $presentados = $cuenta('presentado');
        $empresa = Empresa::withoutGlobalScopes()->find($empresaId);

        return [
            'desde'          => $this->inicioDeGestion($empresa)?->translatedFormat('F \d\e Y'),
            'total'          => count($periodos),
            'presentados'    => $presentados,
            'pendientes'     => count($periodos) - $presentados,
            'vencidos'       => $vencidos,
            'al_dia'         => $vencidos === 0,
            'sin_movimiento' => count(array_filter($periodos, fn ($p) => $p['movimiento']['vacio'])),
        ];
    }
}
