<?php

namespace App\Filament\Contabilidad\Pages;

use App\Jobs\GenerarDeclaracion;
use App\Models\Declaracion;
use App\Models\JournalEntry;
use App\Models\Retencion;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use App\Services\ContabilidadService;
use App\Services\ServicioSri;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Declaraciones del SRI: formulario 104, 103 y anexo transaccional.
 *
 * Pedir una declaración no devuelve el archivo: devuelve un número de
 * seguimiento y el trabajo se va a la cola. El ERP calcula los casilleros desde
 * el libro diario y el microservicio escribe el XML; cuando termina, llega el
 * aviso a la campana. Un anexo de un mes cargado no cabe en una petición HTTP.
 */
class Declaraciones extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Declaraciones';
    protected static ?string $title           = 'Declaraciones';
    protected static ?int    $navigationSort  = 8;
    protected static string  $view            = 'filament.contabilidad.declaraciones';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    /**
     * Los casilleros del 101 que el ERP ya puede llenar.
     *
     * Cada uno sale de un saldo real, no de una declaración del contribuyente:
     * ese fue el problema del 101 que se hizo a mano.
     *
     * @return array<int, array{concepto: string, valor: string, etiqueta: string}>
     */
    public function conceptos101(int $anio): array
    {
        $empresa = Filament::getTenant();
        $saldos = app(ContabilidadService::class)->saldos($empresa->id, "{$anio}-12-31", $anio);

        $retenido = (float) \App\Models\RetencionRecibida::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)->whereYear('fecha', $anio)
            ->where('tipo', 'renta')->sum('valor');

        $noDeducible = (float) \App\Models\Gasto::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)->whereYear('fecha', $anio)
            ->where('estado', 'confirmado')->sum('no_deducible');

        // Conceptos del esquema del SRI verificados en el 101 que ya se presentó.
        $mapa = [
            ['1080', $saldos['activo'],     'Total activo'],
            ['1620', $saldos['pasivo'],     'Total pasivo'],
            ['1780', $saldos['patrimonio'], 'Total patrimonio neto'],
            ['1930', $saldos['ingresos'],   'Total ingresos'],
            ['3360', $saldos['costos'],     'Costo de ventas'],
            ['3380', $saldos['costos'] + $saldos['gastos'], 'Total costos y gastos'],
            ['3430', abs($saldos['resultado']), $saldos['resultado'] >= 0 ? 'Utilidad del ejercicio' : 'Pérdida del ejercicio'],
        ];

        if ($noDeducible > 0) {
            $mapa[] = ['3470', $noDeducible, 'Gastos no deducibles'];
        }

        if ($retenido > 0) {
            $mapa[] = ['3610', $retenido, 'Retenciones en la fuente que le practicaron'];
        }

        return collect($mapa)
            ->filter(fn ($f) => abs((float) $f[1]) > 0.001)
            ->map(fn ($f) => ['concepto' => $f[0], 'valor' => number_format((float) $f[1], 2, '.', ''), 'etiqueta' => $f[2]])
            ->values()->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('auditar_101')
                ->label('Revisar el 101 del ' . (now()->year - 1))
                ->icon('heroicon-o-shield-check')
                ->action(function () {
                    $anio = now()->year - 1;
                    $empresa = Filament::getTenant();
                    $r = app(ServicioSri::class)->auditar101(
                        $empresa->numero_identificacion ?: '', $anio, $this->conceptos101($anio));

                    if (! ($r['ok'] ?? false)) {
                        Notification::make()->title('No se pudo revisar')->body($r['error'] ?? 'sin detalle')
                            ->warning()->persistent()->send();
                        return;
                    }

                    $cuadres = collect($r['cuadres'] ?? [])
                        ->map(fn ($c) => $c['nombre'] . ': ' . ($c['cuadra'] ? 'cuadra' : 'difiere en ' . $c['diferencia']))
                        ->join(' · ');

                    Notification::make()
                        ->title($r['casilleros_incluidos'] . ' casilleros listos')
                        ->body(trim($cuadres . ' ' . implode(' · ', $r['avisos'] ?? [])))
                        ->success()->persistent()->send();
                }),

            Action::make('descargar_101')
                ->label('Generar el XML del 101')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $anio = now()->year - 1;
                    $empresa = Filament::getTenant();
                    $ruc = $empresa->numero_identificacion ?: '';
                    $r = app(ServicioSri::class)->formulario101($ruc, $anio, $this->conceptos101($anio));

                    if (! ($r['ok'] ?? false)) {
                        Notification::make()->title('No se generó el archivo')->body($r['error'] ?? 'sin detalle')
                            ->danger()->persistent()->send();
                        return;
                    }

                    return response()->streamDownload(
                        fn () => print($r['xml']),
                        "formulario_101_{$anio}_{$ruc}.xml",
                        ['Content-Type' => 'application/xml'],
                    );
                }),

            $this->accionPedir('f104', 'Pedir el 104 del mes', 'heroicon-o-calculator'),
            $this->accionPedir('ats', 'Pedir el anexo transaccional', 'heroicon-o-document-duplicate'),
        ];
    }

    /**
     * Encola una declaración y devuelve el número de seguimiento.
     *
     * No espera al resultado: quien pide un anexo se va a hacer otra cosa y
     * vuelve cuando suena la campana.
     */
    private function accionPedir(string $tipo, string $etiqueta, string $icono): Action
    {
        return Action::make("pedir_{$tipo}")
            ->label($etiqueta)
            ->icon($icono)
            ->color($tipo === 'f104' ? 'primary' : 'gray')
            ->form([
                Select::make('anio')
                    ->label('Año')
                    ->options(collect(range(now()->year, now()->year - 3))->mapWithKeys(fn ($a) => [$a => $a]))
                    ->default(now()->year)
                    ->required(),
                Select::make('mes')
                    ->label('Mes')
                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [
                        $m => \Illuminate\Support\Carbon::create(null, $m)->translatedFormat('F'),
                    ]))
                    ->default(now()->subMonth()->month)
                    ->required(),
            ])
            ->action(function (array $data) use ($tipo, $etiqueta) {
                $declaracion = Declaracion::create([
                    'empresa_id'     => Filament::getTenant()->id,
                    'tipo'           => $tipo,
                    'anio'           => (int) $data['anio'],
                    'mes'            => (int) $data['mes'],
                    'estado'         => 'pendiente',
                    'solicitado_por' => auth()->id(),
                ]);

                GenerarDeclaracion::dispatch($declaracion->id);

                Notification::make()
                    ->title('Se está generando')
                    ->body("Seguimiento {$declaracion->seguimiento}. Te aviso aquí mismo cuando esté.")
                    ->info()
                    ->send();
            });
    }

    /** Baja el XML ya generado. */
    public function descargar(int $id)
    {
        $declaracion = Declaracion::where('empresa_id', Filament::getTenant()->id)->findOrFail($id);

        abort_unless($declaracion->archivo && Storage::disk('local')->exists($declaracion->archivo), 404);

        return response()->streamDownload(
            fn () => print(Storage::disk('local')->get($declaracion->archivo)),
            basename($declaracion->archivo),
            ['Content-Type' => 'application/xml'],
        );
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $periodos = [];

        // Los seis últimos meses, con lo que hoy se puede calcular del ERP.
        for ($i = 0; $i < 6; $i++) {
            $mes = now()->startOfMonth()->subMonths($i);
            $desde = $mes->toDateString();
            $hasta = $mes->copy()->endOfMonth()->toDateString();

            $iva = (float) DB::table('journal_entry_lines as l')
                ->join('journal_entries as a', 'a.id', '=', 'l.journal_entry_id')
                ->join('account_plans as c', 'c.id', '=', 'l.account_plan_id')
                ->where('a.empresa_id', $empresa->id)
                ->where('a.status', 'confirmado')
                ->whereBetween('a.fecha', [$desde, $hasta])
                ->where('c.code', 'like', '2.1.04%')
                ->sum(DB::raw('l.haber - l.debe'));

            $retenido = (float) Retencion::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->whereBetween('fecha', [$desde, $hasta])
                ->sum('total_retenido');

            $comprobantes = JournalEntry::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->whereBetween('fecha', [$desde, $hasta])
                ->whereIn('tipo', ['compra', 'venta'])
                ->count();

            $periodos[] = [
                'etiqueta'     => $mes->translatedFormat('F \d\e Y'),
                'iva'          => max($iva, 0),
                'retenido'     => $retenido,
                'comprobantes' => $comprobantes,
                'cerrado'      => $mes->isBefore(now()->startOfMonth()),
            ];
        }

        $anio = now()->year - 1;

        return [
            'empresa'    => $empresa,
            'periodos'   => $periodos,
            'servicio'   => app(ServicioSri::class)->salud(),
            'anio101'    => $anio,
            'conceptos'  => $this->conceptos101($anio),
            'pedidos'    => Declaracion::where('empresa_id', $empresa->id)
                ->latest('id')->limit(12)->get(),
        ];
    }
}
