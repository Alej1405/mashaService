<?php

namespace App\Services;

use App\Models\ComprobanteSri;
use App\Models\Declaracion;
use App\Services\ContabilidadService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Los hallazgos de un período antes de cerrarlo.
 *
 * Un mes solo se cierra cuando la declaración salió **del sistema**: entonces
 * cada cifra tiene detrás su asiento, su documento y su movimiento, y se puede
 * responder por ella en una fiscalización. Una declaración cargada desde el PDF
 * del portal da trazabilidad del crédito tributario y sirve para posicionar a la
 * empresa, pero no respalda el cierre: el sistema no tiene los movimientos que
 * la sustentan y no puede afirmar que cuadren.
 *
 * Por eso, antes de cerrar, esto revisa lo que sí puede comprobar y devuelve lo
 * que no cuadra, para que se solvente en lugar de arrastrarlo.
 *
 * Marco: skills `contabilidad-ec` (integridad cruzada) y `declaraciones-sri-ec`.
 */
class AuditoriaPeriodoService
{
    /** Los hallazgos que impiden cerrar, frente a los que solo avisan. */
    public const BLOQUEA = 'bloquea';
    public const REVISAR = 'revisar';
    public const AVISO   = 'aviso';

    public function __construct(private readonly ContabilidadService $contabilidad)
    {
    }

    /**
     * @return array{hallazgos: array<int, array>, bloqueantes: int, puede_cerrar: bool}
     */
    public function revisar(int $empresaId, int $anio, int $mes): array
    {
        $desde = Carbon::create($anio, $mes, 1)->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();

        $hallazgos = array_merge(
            $this->asientosDescuadrados($empresaId, $desde, $hasta),
            $this->documentosSinAsiento($empresaId, $desde, $hasta),
            $this->ventasSinCosto($empresaId, $desde, $hasta),
            $this->inventarioContraKardex($empresaId, $hasta),
            $this->cuentasSinLinea($empresaId),
            $this->comprobantesSinConciliar($empresaId, $anio, $mes),
            $this->arrastreDelMesAnterior($empresaId, $anio, $mes),
            $this->declaracionCargadaEncima($empresaId, $anio, $mes),
        );

        $bloqueantes = count(array_filter($hallazgos, fn ($h) => $h['nivel'] === self::BLOQUEA));

        return [
            'hallazgos'    => $hallazgos,
            'bloqueantes'  => $bloqueantes,
            'puede_cerrar' => $bloqueantes === 0,
        ];
    }

    /** Un asiento que no cuadra invalida todo lo que se calcule con él. */
    private function asientosDescuadrados(int $empresaId, Carbon $desde, Carbon $hasta): array
    {
        $malos = DB::table('journal_entries')
            ->where('empresa_id', $empresaId)->where('status', 'confirmado')
            ->whereBetween('fecha', [$desde, $hasta])
            ->where(fn ($q) => $q->where('esta_cuadrado', false)
                ->orWhereRaw('abs(total_debe - total_haber) > 0.01'))
            ->count();

        return $malos === 0 ? [] : [[
            'nivel'   => self::BLOQUEA,
            'titulo'  => "{$malos} asientos del mes no cuadran",
            'detalle' => 'El debe y el haber difieren. Todo lo que se calcule con ellos —el balance, '
                . 'el 104— sale mal. Corrígelos antes de cerrar.',
            'donde'   => 'Libro diario',
        ]];
    }

    /** Una compra o una venta sin asiento no existe para la contabilidad. */
    private function documentosSinAsiento(int $empresaId, Carbon $desde, Carbon $hasta): array
    {
        $compras = DB::table('purchases')->where('empresa_id', $empresaId)
            ->where('status', 'confirmado')->whereBetween('date', [$desde, $hasta])
            ->whereNull('journal_entry_id')->count();

        $ventas = DB::table('sales')->where('empresa_id', $empresaId)
            ->where('estado', 'confirmado')->whereBetween('fecha', [$desde, $hasta])
            ->whereNull('journal_entry_id')->count();

        $gastos = DB::table('gastos')->where('empresa_id', $empresaId)
            ->where('estado', 'confirmado')->whereBetween('fecha', [$desde, $hasta])
            ->whereNull('journal_entry_id')->count();

        $total = $compras + $ventas + $gastos;

        return $total === 0 ? [] : [[
            'nivel'   => self::BLOQUEA,
            'titulo'  => "{$total} documentos confirmados sin asiento",
            'detalle' => trim(($compras ? "{$compras} compras · " : '') . ($ventas ? "{$ventas} ventas · " : '')
                . ($gastos ? "{$gastos} gastos" : ''), ' ·')
                . '. Están en el 104 pero no en el balance: las dos cifras no van a cuadrar.',
            'donde'   => 'Compras, ventas y gastos',
        ]];
    }

    /** La venta genera dos asientos: el ingreso y la descarga del costo. */
    private function ventasSinCosto(int $empresaId, Carbon $desde, Carbon $hasta): array
    {
        $conCosto = DB::table('sales as v')
            ->join('journal_entry_lines as l', 'l.journal_entry_id', '=', 'v.journal_entry_id')
            ->join('account_plans as c', 'c.id', '=', 'l.account_plan_id')
            ->where('v.empresa_id', $empresaId)->where('v.estado', 'confirmado')
            ->whereBetween('v.fecha', [$desde, $hasta])
            ->where('c.code', 'like', '5%')
            ->distinct('v.id')->count('v.id');

        $total = DB::table('sales')->where('empresa_id', $empresaId)
            ->where('estado', 'confirmado')->whereBetween('fecha', [$desde, $hasta])
            ->whereNotNull('journal_entry_id')->count();

        $sinCosto = $total - $conCosto;

        return ($total === 0 || $sinCosto <= 0) ? [] : [[
            'nivel'   => self::REVISAR,
            'titulo'  => "{$sinCosto} de {$total} ventas no descargan costo",
            'detalle' => 'La venta registra el ingreso pero no baja el inventario contra costo de '
                . 'ventas. Eso infla la utilidad y el inventario a la vez; es el hallazgo que más '
                . 'caro sale en una auditoría.',
            'donde'   => 'Ventas',
        ]];
    }

    /** El saldo contable de inventarios y el kardex valorado son lo mismo. */
    private function inventarioContraKardex(int $empresaId, Carbon $hasta): array
    {
        $contable = (float) DB::table('journal_entry_lines as l')
            ->join('journal_entries as a', 'a.id', '=', 'l.journal_entry_id')
            ->join('account_plans as c', 'c.id', '=', 'l.account_plan_id')
            ->where('a.empresa_id', $empresaId)->where('a.status', 'confirmado')
            ->whereDate('a.fecha', '<=', $hasta)
            ->where('c.code', 'like', '1.1.03%')
            ->sum(DB::raw('l.debe - l.haber'));

        $kardex = (float) DB::table('inventory_items')
            ->where('empresa_id', $empresaId)
            ->whereIn('type', ['insumo', 'materia_prima', 'producto_terminado', 'producto_en_proceso'])
            ->sum('saldo_valorado');

        $diferencia = round($contable - $kardex, 2);

        return abs($diferencia) < 0.01 ? [] : [[
            'nivel'   => self::REVISAR,
            'titulo'  => 'El inventario contable no cuadra con el kardex',
            'detalle' => 'La cuenta dice $ ' . number_format($contable, 2, ',', '.')
                . ' y el kardex valorado $ ' . number_format($kardex, 2, ',', '.')
                . ' — difieren en $ ' . number_format(abs($diferencia), 2, ',', '.')
                . '. Hay un movimiento sin asiento o un asiento sin movimiento.',
            'donde'   => 'Inventario',
        ]];
    }

    /** Sin línea del estado, la cuenta desaparece del balance de la SCVS. */
    private function cuentasSinLinea(int $empresaId): array
    {
        $sin = app(SuperciasService::class)->cuentasSinCodigo($empresaId);

        return $sin === 0 ? [] : [[
            'nivel'   => self::BLOQUEA,
            'titulo'  => "{$sin} cuentas con movimiento sin línea del estado",
            'detalle' => 'No aparecen en el balance que recibe la Superintendencia. El mapeo es '
                . 'automático, así que si quedan sin línea es que algo falló al crearlas.',
            'donde'   => 'Revisar mapeo SCVS',
        ]];
    }

    /**
     * Lo importado del portal suma al 104 pero no tiene asiento detrás.
     *
     * Recibido y emitido no son el mismo hallazgo. Una factura recibida sin
     * registrar es una compra que falta clasificar, y se resuelve en la bandeja.
     * Una **emitida** sin registrar es una venta que la empresa facturó y que
     * el ERP no conoce: falta un ingreso, y con él su IVA y su costo. Eso no se
     * revisa, eso cierra el paso.
     */
    private function comprobantesSinConciliar(int $empresaId, int $anio, int $mes): array
    {
        $base = fn () => ComprobanteSri::where('empresa_id', $empresaId)
            ->delPeriodo($anio, $mes)->sinConciliar();

        $recibidos = (clone $base())->where('origen', 'recibido')->count();
        $emitidos  = (clone $base())->where('origen', 'emitido')->count();

        $hallazgos = [];

        if ($recibidos) {
            $hallazgos[] = [
                'nivel'   => self::REVISAR,
                'titulo'  => "{$recibidos} facturas recibidas sin registrar en el ERP",
                'detalle' => 'Suman al formulario pero no tienen asiento: el 104 y el balance van a '
                    . 'diferir en ese monto. Di qué fue cada una y el sistema crea la compra o el gasto.',
                'donde'   => 'Clasificar comprobantes',
            ];
        }

        if ($emitidos) {
            $hallazgos[] = [
                'nivel'   => self::BLOQUEA,
                'titulo'  => "{$emitidos} facturas emitidas que el ERP no tiene",
                'detalle' => 'La empresa las facturó y el portal las ve, pero aquí no existe la venta: '
                    . 'faltan el ingreso, su IVA y la descarga del costo. Regístralas en ventas.',
                'donde'   => 'Ventas',
            ];
        }

        return $hallazgos;
    }

    /** Sin el mes anterior, el crédito tributario arranca en cero. */
    private function arrastreDelMesAnterior(int $empresaId, int $anio, int $mes): array
    {
        $previo = Carbon::create($anio, $mes, 1)->subMonth();
        $empresa = \App\Models\Empresa::withoutGlobalScopes()->find($empresaId);
        $inicio = $empresa ? app(GestionSriService::class)->inicioDeGestion($empresa) : null;

        if ($inicio && $previo->lessThan($inicio)) {
            return [];
        }

        $existe = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
            ->where('anio', $previo->year)->where('mes', $previo->month)
            ->where(fn ($q) => $q->listas()->orWhereNotNull('presentado_en'))
            ->exists();

        return $existe ? [] : [[
            'nivel'   => self::REVISAR,
            'titulo'  => 'Falta el 104 de ' . $previo->format('m/Y'),
            'detalle' => 'Sin él, los casilleros de arrastre quedan en cero y se pierde el crédito '
                . 'tributario que venía de ese mes. Cárgalo desde el PDF del portal si ya se presentó.',
            'donde'   => 'Declaraciones · cargar presentada',
        ]];
    }

    /** Si ya hay una cargada, generar otra crea dos verdades del mismo mes. */
    private function declaracionCargadaEncima(int $empresaId, int $anio, int $mes): array
    {
        $cargada = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
            ->where('anio', $anio)->where('mes', $mes)->cargadas()->first();

        return ! $cargada ? [] : [[
            'nivel'   => self::AVISO,
            'titulo'  => 'Este período ya tiene una declaración cargada del portal',
            'detalle' => 'Presentada el ' . ($cargada->presentado_en?->format('d/m/Y') ?? 'sin fecha')
                . '. La que genere el sistema debería dar lo mismo: si difiere, es que al ERP le '
                . 'faltan movimientos de ese mes.',
            'donde'   => 'Declaraciones',
        ]];
    }

    /**
     * Compara lo que el sistema calcula con lo que se declaró de verdad.
     *
     * Es la prueba de fuego de la trazabilidad: si el ERP tiene todos los
     * movimientos del mes, sus cifras y las del comprobante presentado
     * coinciden. Cuando no, el hueco se ve casillero a casillero.
     *
     * @return array<int, array{casillero: string, concepto: string, sistema: float, declarado: float, diferencia: float}>
     */
    public function compararConLoDeclarado(int $empresaId, int $anio, int $mes): array
    {
        $cargada = Declaracion::where('empresa_id', $empresaId)->where('tipo', 'f104')
            ->where('anio', $anio)->where('mes', $mes)->cargadas()->first();

        if (! $cargada) {
            return [];
        }

        $calculado = app(Formulario104Service::class)->calcular($empresaId, $anio, $mes)['casilleros'];
        $declarado = $cargada->datos['casilleros'] ?? [];

        $mirar = [
            '419' => 'Total ventas',
            '429' => 'IVA en ventas',
            '519' => 'Total compras',
            '529' => 'IVA en compras',
            '605' => 'Crédito del mes anterior',
            '615' => 'Crédito al mes siguiente',
            '999' => 'Total pagado',
        ];

        $diferencias = [];

        foreach ($mirar as $casillero => $concepto) {
            $s = round((float) ($calculado[$casillero] ?? 0), 2);
            $d = round((float) ($declarado[$casillero] ?? 0), 2);

            if (abs($s - $d) > 0.01) {
                $diferencias[] = ['casillero' => $casillero, 'concepto' => $concepto,
                                  'sistema' => $s, 'declarado' => $d, 'diferencia' => round($s - $d, 2)];
            }
        }

        return $diferencias;
    }
}
