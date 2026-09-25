<?php

namespace App\Filament\Contabilidad\Pages;

use App\Jobs\GenerarDeclaracion;
use App\Models\Declaracion;
use App\Services\GestionSriService;
use App\Services\ImportadorComprobantesSri;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Cumplimiento: desde cuándo respondemos y qué falta.
 *
 * El panel de declaraciones dice qué se generó. Esta pantalla dice otra cosa,
 * que es la que importa cuando alguien pregunta si estamos al día: desde qué
 * mes el ERP responde por esta empresa, qué períodos van presentados, cuáles
 * están vencidos y cuáles no tienen ni un movimiento registrado.
 */
class Cumplimiento extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Cumplimiento';
    protected static ?string $navigationGroup = 'Impuestos';
    protected static ?string $title           = 'Cumplimiento tributario';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.contabilidad.cumplimiento';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function getNavigationBadge(): ?string
    {
        $empresa = Filament::getTenant();

        if (! $empresa) {
            return null;
        }

        $vencidos = app(GestionSriService::class)->cumplimiento($empresa->id)['vencidos'];

        return $vencidos > 0 ? (string) $vencidos : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->accionInicioDeGestion(),
            $this->accionImportar(),
        ];
    }

    /**
     * La raya en el suelo: desde qué mes el ERP responde.
     *
     * No es la fecha de constitución de la empresa. Es desde cuándo el sistema
     * tiene la información completa y, por tanto, desde cuándo puede afirmar
     * que falta algo.
     */
    private function accionInicioDeGestion(): Action
    {
        return Action::make('inicio_gestion')
            ->label('Inicio de gestión')
            ->icon('heroicon-o-flag')
            ->color('gray')
            ->fillForm(fn () => [
                'inicio_gestion_erp' => Filament::getTenant()->inicio_gestion_erp,
                'periodicidad_iva'   => Filament::getTenant()->periodicidad_iva ?? 'mensual',
            ])
            ->form([
                Placeholder::make('explicacion')
                    ->label('')
                    ->content('Desde este mes el ERP responde por las declaraciones de la empresa. '
                        . 'No es la fecha en que nació la compañía: es desde cuándo la información '
                        . 'está completa aquí. Lo anterior no se cuenta como pendiente.'),

                DatePicker::make('inicio_gestion_erp')
                    ->label('Primer mes que declaramos desde el ERP')
                    ->displayFormat('m/Y')
                    ->required(),

                Select::make('periodicidad_iva')
                    ->label('Periodicidad del IVA')
                    ->options(['mensual' => 'Mensual', 'semestral' => 'Semestral (régimen RIMPE)'])
                    ->required(),
            ])
            ->action(function (array $data) {
                Filament::getTenant()->update([
                    'inicio_gestion_erp' => Carbon::parse($data['inicio_gestion_erp'])->startOfMonth(),
                    'periodicidad_iva'   => $data['periodicidad_iva'],
                ]);

                Notification::make()->title('Inicio de gestión guardado')->success()->send();
            });
    }

    /** Los listados que la empresa baja del portal del SRI. */
    private function accionImportar(): Action
    {
        return Action::make('importar')
            ->label('Importar del portal del SRI')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->form([
                Placeholder::make('ayuda')
                    ->label('')
                    ->content('Sirve para los meses que no están registrados en el sistema. '
                        . 'Sube el listado de comprobantes recibidos o el de emitidos, tal como '
                        . 'los descarga el portal. Lo que ya esté en el ERP no se cuenta dos veces.'),

                FileUpload::make('archivo')
                    ->label('Archivo .txt del SRI')
                    ->acceptedFileTypes(['text/plain', 'application/octet-stream'])
                    ->maxSize(10240)
                    ->disk('local')
                    ->directory('sri/temporal')
                    ->required(),
            ])
            ->action(function (array $data) {
                $ruta = storage_path('app/private/' . $data['archivo']);

                if (! file_exists($ruta)) {
                    $ruta = storage_path('app/' . $data['archivo']);
                }

                $r = app(ImportadorComprobantesSri::class)
                    ->importar(Filament::getTenant()->id, file_get_contents($ruta));

                @unlink($ruta);

                if (! ($r['ok'] ?? false)) {
                    Notification::make()->title('No se pudo importar')
                        ->body($r['error'])->danger()->persistent()->send();

                    return;
                }

                $periodos = implode(', ', array_keys($r['periodos'] ?? []));

                Notification::make()
                    ->title("{$r['importados']} comprobantes importados")
                    ->body(trim("{$r['tipo']} · {$r['repetidos']} ya estaban · {$r['conciliados']} casan con el ERP"
                        . ($periodos ? " · períodos: {$periodos}" : '')
                        . ' ' . implode(' ', $r['avisos'] ?? [])))
                    ->success()->persistent()->send();
            });
    }

    /** Pide la declaración de un período desde la propia fila. */
    public function pedir(int $anio, int $mes, string $tipo = 'f104'): void
    {
        $declaracion = Declaracion::create([
            'empresa_id'     => Filament::getTenant()->id,
            'tipo'           => $tipo,
            'anio'           => $anio,
            'mes'            => $mes,
            'estado'         => 'pendiente',
            'solicitado_por' => auth()->id(),
        ]);

        GenerarDeclaracion::dispatch($declaracion->id);

        Notification::make()->title('Se está generando')
            ->body("Seguimiento {$declaracion->seguimiento}.")->info()->send();
    }

    /**
     * Deja constancia de que el período se presentó, y lo cierra.
     *
     * Un mes presentado que se puede recalcular va a dar distinto la próxima
     * vez, y entonces lo que consta en el SRI y lo que dice el sistema dejan de
     * cuadrar. Marco: skill `contabilidad-ec`, cierre mensual.
     */
    public function marcarPresentado(int $declaracionId): void
    {
        $declaracion = Declaracion::where('empresa_id', Filament::getTenant()->id)
            ->findOrFail($declaracionId);

        $declaracion->update(['presentado_en' => now()]);

        // Solo cierra el mes lo que salió del sistema: una declaración cargada
        // del portal no tiene asientos detrás que respalden el cierre.
        if ($declaracion->es_cargada) {
            Notification::make()
                ->title('Marcado como presentado')
                ->body("{$declaracion->periodo} consta en el histórico, pero el período sigue abierto: "
                    . 'esa declaración se cargó del portal y el ERP no tiene los movimientos que la sustentan.')
                ->warning()->persistent()->send();

            return;
        }

        $revision = app(\App\Services\AuditoriaPeriodoService::class)
            ->revisar($declaracion->empresa_id, $declaracion->anio, $declaracion->mes);

        if (! $revision['puede_cerrar']) {
            $lista = collect($revision['hallazgos'])
                ->where('nivel', \App\Services\AuditoriaPeriodoService::BLOQUEA)
                ->map(fn ($h) => '· ' . $h['titulo'])->join(' ');

            Notification::make()
                ->title('Presentado, pero el período no se cerró')
                ->body("Hay {$revision['bloqueantes']} hallazgos que hay que solventar antes: {$lista}")
                ->danger()->persistent()->send();

            return;
        }

        app(\App\Services\ContabilidadService::class)->cerrarMes(
            $declaracion->empresa_id, $declaracion->anio, $declaracion->mes, $declaracion->id,
        );

        $porRevisar = count($revision['hallazgos']);

        Notification::make()
            ->title('Presentado y período cerrado')
            ->body("{$declaracion->periodo} ya no admite asientos. Para corregirlo hace falta una sustitutiva."
                . ($porRevisar ? " Quedan {$porRevisar} hallazgos por mirar, ninguno bloqueante." : ''))
            ->success()->persistent()->send();
    }

    /** Los hallazgos de un período, para verlos antes de cerrarlo. */
    public function hallazgosDe(int $anio, int $mes): array
    {
        return app(\App\Services\AuditoriaPeriodoService::class)
            ->revisar(Filament::getTenant()->id, $anio, $mes);
    }

    /**
     * Lo que el sistema calculó, antes de bajar nada.
     *
     * Nadie debería descargar un XML sin haber visto qué IVA generó, cuánto
     * crédito aplica y qué le queda a favor. Marco: skill `blade-filament`,
     * visibilidad del estado.
     *
     * @return array<string, mixed>|null
     */
    public function resumenDe(int $declaracionId): ?array
    {
        $d = Declaracion::where('empresa_id', Filament::getTenant()->id)->find($declaracionId);

        if (! $d || ! $d->datos) {
            return null;
        }

        return [
            'periodo'       => $d->periodo,
            'iva_ventas'    => $d->casillero('429'),
            'iva_compras'   => $d->casillero('529'),
            'credito_mes'   => $d->casillero('564'),
            'credito_previo' => $d->casillero('605') + $d->casillero('606'),
            'causado'       => $d->casillero('601'),
            'a_favor'       => $d->casillero('602'),
            'retenido'      => $d->casillero('799'),
            'a_pagar'       => $d->casillero('999'),
            'arrastra'      => $d->casillero('615') + $d->casillero('617'),
            'avisos'        => $d->avisos ?? [],
        ];
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $gestion = app(GestionSriService::class);

        return [
            'empresa'      => $empresa,
            'cumplimiento' => $gestion->cumplimiento($empresa->id),
            'periodos'     => $gestion->periodos($empresa->id, 12),
            'sinMapear'    => app(\App\Services\SuperciasService::class)->cuentasSinCodigo($empresa->id),
            'porRevisar'   => app(\App\Services\MapeoSuperciasService::class)->porRevisar($empresa->id),
            'importados'   => \App\Models\ComprobanteSri::where('empresa_id', $empresa->id)->count(),
        ];
    }
}
