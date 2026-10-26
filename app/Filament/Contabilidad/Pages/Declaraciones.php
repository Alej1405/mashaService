<?php

namespace App\Filament\Contabilidad\Pages;

use App\Jobs\GenerarDeclaracion;
use App\Models\Declaracion;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use App\Services\ContabilidadService;
use App\Services\ServicioSri;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Declaraciones del SRI: anexo transaccional, histórico y formulario 101.
 *
 * El ATS sí es un archivo: pedirlo devuelve un número de seguimiento, el trabajo
 * se va a la cola y el microservicio escribe el XML. El 104 no: el portal no
 * deja subirlo, así que se declara en Cumplimiento escribiendo sus casillas.
 */
class Declaraciones extends Page
{
    use \App\Filament\Contabilidad\Concerns\GestionaDeclaraciones;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Declaraciones';
    protected static ?string $navigationGroup = 'Impuestos';
    protected static ?string $title           = 'Declaraciones';
    protected static ?int    $navigationSort  = 2;
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

            $this->accionCargarPresentadas(),
            $this->cargarConfirmacionesAtsAction(),
            // El 104 no es un archivo: se declara en Cumplimiento, casilla por casilla.
            Action::make('declarar_iva')
                ->label('Declarar el IVA')
                ->icon('heroicon-o-calculator')
                ->url(fn () => Cumplimiento::getUrl()),
            $this->accionPedir('ats', 'Pedir el anexo transaccional', 'heroicon-o-document-duplicate'),
        ];
    }

    /**
     * Sube los comprobantes en PDF de las declaraciones ya presentadas.
     *
     * Es lo primero que hace un contador que llega con historial: sube los
     * PDF de dos años y el sistema saca de cada uno sus 152 casilleros, con
     * lo que queda rastreado el crédito tributario mes a mes.
     *
     * No cierra ningún período: eso solo lo hace una declaración generada
     * aquí, que tiene los asientos detrás.
     */
    private function accionCargarPresentadas(): Action
    {
        return Action::make('cargar_presentadas')
            ->label('Cargar declaraciones del 104')
            ->icon('heroicon-o-document-arrow-up')
            ->color('gray')
            ->modalHeading('Subir los comprobantes del portal')
            ->modalDescription('Las declaraciones que se descargan en SRI en línea → Declaraciones '
                . 'presentadas (el PDF de varias páginas con las casillas, no el comprobante de pago). Puedes '
                . 'subir varias de golpe. De cada una se leen sus casillas y el crédito tributario que va de un '
                . 'mes al siguiente. Si un mes se marcó presentado sin su declaración, esta reemplaza lo que '
                . 'había calculado el ERP. Las de otra empresa o de otro formulario se rechazan solas.')
            ->modalSubmitActionLabel('Subir y leer')
            ->form([
                \Filament\Forms\Components\FileUpload::make('archivos')
                    ->label('Comprobantes en PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->multiple()
                    ->maxFiles(36)
                    ->maxSize(5120)
                    // El PDF no se guarda: de él solo salen los casilleros, que
                    // van a la base. Persistirlo obligaba a crear una carpeta
                    // en el storage y bastaba con que sus permisos no fueran de
                    // www-data para que la subida entera devolviera un 500.
                    ->storeFiles(false)
                    ->required()
                    ->helperText('Hasta 36 de una vez, que son tres años de declaraciones mensuales.'),
            ])
            ->action(function (array $data) {
                $empresa = Filament::getTenant();
                $archivos = [];

                foreach ((array) $data['archivos'] as $archivo) {
                    // Llegan como archivo temporal de Livewire: se lee en
                    // memoria y se descarta.
                    if ($archivo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $archivos[] = [
                            'contenido' => $archivo->get(),
                            'nombre'    => $archivo->getClientOriginalName(),
                        ];
                        $archivo->delete();

                        continue;
                    }

                    // Por si alguno quedó en disco de una subida anterior.
                    $completa = storage_path('app/private/' . $archivo);

                    if (file_exists($completa)) {
                        $archivos[] = ['contenido' => file_get_contents($completa), 'nombre' => basename($archivo)];
                        @unlink($completa);
                    }
                }

                $r = app(\App\Services\ImportadorDeclaracionesPdf::class)
                    ->importar($empresa->id, $archivos, auth()->id());

                $cadena = $r['cadena'] ?? [];
                $cuerpo = "{$r['cargadas']} cargadas"
                    . (($r['reemplazadas'] ?? []) ? ' · reemplazadas con lo presentado: ' . implode(', ', $r['reemplazadas']) : '')
                    . ($r['repetidas'] ? " · {$r['repetidas']} ya estaban" : '')
                    . (count($r['rechazadas']) ? ' · ' . count($r['rechazadas']) . ' rechazadas' : '');

                foreach ($r['rechazadas'] as $x) {
                    $cuerpo .= " — {$x['archivo']}: {$x['motivo']}";
                }

                if ($faltan = ($cadena['faltantes'] ?? [])) {
                    $cuerpo .= '. Faltan meses en el histórico: ' . implode(', ', $faltan)
                        . '. Sin ellos no se puede verificar de dónde viene el crédito tributario '
                        . 'de los meses siguientes.';
                } elseif (($cadena['cadena_cuadra'] ?? false) && ($cadena['periodos'] ?? 0) > 1) {
                    $cuerpo .= '. El crédito tributario encadena sin saltos: $ '
                        . number_format($cadena['credito_actual'] ?? 0, 2, ',', '.')
                        . ' a favor al ' . ($cadena['ultimo_periodo'] ?? '—') . '.';
                } elseif (! ($cadena['cadena_cuadra'] ?? true)) {
                    $cuerpo .= '. Ojo: el crédito tributario no encadena en '
                        . count($cadena['saltos'] ?? []) . ' puntos. Falta alguna declaración por cargar.';
                }

                Notification::make()
                    ->title(($r['cargadas'] || ($r['reemplazadas'] ?? [])) ? 'Declaraciones cargadas al histórico' : 'No se cargó ninguna')
                    ->body($cuerpo)
                    ->{($r['cargadas'] || ($r['reemplazadas'] ?? [])) ? 'success' : 'warning'}()
                    ->persistent()
                    ->send();
            });
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
            // Un mes, una declaración: si ya existe sin presentar, se vuelve a calcular.
            ->action(fn (array $data) => $this->pedir((int) $data['anio'], (int) $data['mes'], $tipo));
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
        $anio = now()->year - 1;

        return [
            'empresa'    => $empresa,
            'servicio'   => app(ServicioSri::class)->salud(),
            'anio101'    => $anio,
            'conceptos'  => $this->conceptos101($anio),
            'pedidos'    => Declaracion::where('empresa_id', $empresa->id)
                ->latest('id')->limit(12)->get(),
            'cadena'     => app(\App\Services\ImportadorDeclaracionesPdf::class)
                ->verificarCadena($empresa->id),
        ];
    }
}
