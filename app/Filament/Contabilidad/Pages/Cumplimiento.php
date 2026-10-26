<?php

namespace App\Filament\Contabilidad\Pages;

use App\Jobs\GenerarDeclaracion;
use App\Models\Declaracion;
use App\Services\GestionSriService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
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

    /** La declaración cuyo «qué declarar» está abierto; null = ninguna. */
    public ?int $abierta = null;

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
            // Aquí la tarea es declarar: subir el archivo del portal es secundario.
            \App\Filament\Contabilidad\Acciones\ImportarDelPortal::make()->color('gray'),
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

    /**
     * Pide la declaración de un período desde la propia fila.
     *
     * Un período tiene una sola declaración: si ya hay una sin presentar, se
     * vuelve a calcular esa misma (por ejemplo, tras registrar facturas que
     * faltaban). Una ya presentada no se toca: corregirla es una sustitutiva.
     */
    public function pedir(int $anio, int $mes, string $tipo = 'f104'): void
    {
        $empresaId = Filament::getTenant()->id;
        $existente = Declaracion::where('empresa_id', $empresaId)->where('tipo', $tipo)
            ->where('anio', $anio)->where('mes', $mes)->latest('id')->first();

        if ($existente?->presentado_en) {
            Notification::make()->title('Ese mes ya está presentado')
                ->body('Para cambiarlo hace falta una declaración sustitutiva.')->warning()->send();

            return;
        }

        $declaracion = $existente ?? new Declaracion(['empresa_id' => $empresaId, 'tipo' => $tipo, 'anio' => $anio, 'mes' => $mes]);
        $declaracion->fill(['estado' => 'pendiente', 'mensaje' => null, 'solicitado_por' => auth()->id()])->save();
        $this->abierta = null;

        GenerarDeclaracion::dispatch($declaracion->id);

        Notification::make()->title('Se está generando')
            ->body("Seguimiento {$declaracion->seguimiento}.")->info()->send();
    }

    /** Abre o cierra lo que hay que escribir en el portal para una declaración. */
    public function verDeclarar(int $declaracionId): void
    {
        $this->abierta = $this->abierta === $declaracionId ? null : $declaracionId;
    }

    /**
     * Sube el comprobante que entrega el SRI al presentar. Es la única forma de dar
     * un 104 por presentado: sin comprobante, el aviso sigue encendido.
     */
    public function subirComprobanteAction(): Action
    {
        return Action::make('subirComprobante')
            ->label('Subir la declaración presentada')
            ->modalHeading(fn (array $arguments) => 'Declaración presentada del 104 · '
                . Declaracion::where('empresa_id', Filament::getTenant()->id)->find($arguments['id'] ?? 0)?->periodo)
            ->modalDescription('Descárgala en SRI en línea → Declaraciones presentadas: es el PDF de varias '
                . 'páginas con todas las casillas. El «Comprobante electrónico para pago» no sirve, porque no '
                . 'las trae. Se comprueba que sea de esta empresa y de este mes, y que cada casilla sea la que '
                . 'calculó el ERP. Con eso el mes queda presentado y cerrado.')
            ->modalSubmitActionLabel('Comprobar y registrar')
            ->form([
                \Filament\Forms\Components\FileUpload::make('pdf')
                    ->label('Declaración en PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(5120)
                    // Se lee en memoria y el servicio lo guarda donde corresponde:
                    // así no depende de los permisos de la carpeta temporal.
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data, array $arguments, Action $action) {
                $declaracion = Declaracion::where('empresa_id', Filament::getTenant()->id)
                    ->where('tipo', 'f104')->findOrFail($arguments['id'] ?? 0);
                $pdf = is_array($data['pdf']) ? reset($data['pdf']) : $data['pdf'];

                $r = app(\App\Services\ComprobanteDeclaracionService::class)
                    ->registrar($declaracion, $pdf->get(), $pdf->getClientOriginalName(), auth()->id());
                $pdf->delete();

                if (! $r['ok']) {
                    $cuerpo = $r['error'];
                    foreach (array_slice($r['diferencias'] ?? [], 0, 6) as $x) {
                        $cuerpo .= " · casilla {$x['codigo']}: el ERP dice " . number_format($x['erp'], 2, ',', '.')
                            . ' y el comprobante ' . number_format($x['sri'], 2, ',', '.');
                    }

                    Notification::make()->title('No se registró la declaración')->body($cuerpo)->danger()->persistent()->send();
                    $action->halt();
                }

                $this->abierta = null;
                $this->cerrarPeriodo($declaracion->fresh());
            });
    }

    /**
     * Con el comprobante registrado, cierra el período.
     *
     * Un mes presentado que se puede recalcular va a dar distinto la próxima
     * vez, y entonces lo que consta en el SRI y lo que dice el sistema dejan de
     * cuadrar. Marco: skill `contabilidad-ec`, cierre mensual.
     */
    private function cerrarPeriodo(Declaracion $declaracion): void
    {
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
            // Lo que se escribe en el portal es la base neta; el IVA lo calcula el formulario.
            'ventas_netas'  => $d->casillero('419'),
            'compras_netas' => $d->casillero('519'),
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
            'declarar'     => $this->paraDeclarar(),
            'ahora'        => $this->ahora($empresa),
        ];
    }

    /**
     * El mes que toca declarar, con la guía ya abierta: quien no sabe de impuestos
     * no tiene por qué adivinar que hay un botón con lo que debe escribir.
     *
     * @return array{alerta: array, declaracion: Declaracion|null, guia: array|null}|null
     */
    private function ahora(\App\Models\Empresa $empresa): ?array
    {
        $alerta = app(GestionSriService::class)->alertaIva($empresa);

        if (! $alerta) {
            return null;
        }

        $d = Declaracion::where('empresa_id', $empresa->id)->where('tipo', 'f104')
            ->where('anio', $alerta['anio'])->where('mes', $alerta['mes'])->latest('id')->first();

        return [
            'alerta'      => $alerta,
            'declaracion' => $d,
            'guia'        => $d?->estado === 'listo' ? $this->paraDeclarar($d->id) : null,
        ];
    }

    /**
     * Lo que se ve al abrir «Qué declarar»: las casillas que se escriben en el
     * portal y los totales que el portal debe mostrar al final, para comparar.
     *
     * @return array{id: int, casillas: array, comparar: array}|null
     */
    private function paraDeclarar(?int $declaracionId = null): ?array
    {
        $id = $declaracionId ?? $this->abierta;
        $d = $id
            ? Declaracion::where('empresa_id', Filament::getTenant()->id)->where('tipo', 'f104')->find($id)
            : null;

        if (! $d || ! $d->datos) {
            return null;
        }

        $comparar = array_filter([
            ['IVA de las ventas', '429', $d->casillero('429')],
            ['IVA de las compras', '529', $d->casillero('529')],
            ['Impuesto a liquidar en el mes', '499', $d->casillero('499')],
            ['Crédito tributario aplicable', '564', $d->casillero('564')],
            ['Impuesto causado', '601', $d->casillero('601')],
            ['Crédito tributario de este período', '602', $d->casillero('602')],
            ['Crédito para el próximo mes · adquisiciones', '615', $d->casillero('615')],
            ['Crédito para el próximo mes · retenciones', '617', $d->casillero('617')],
            ['Total pagado', '999', $d->casillero('999')],
        ], fn ($f) => abs($f[2]) > 0.004 || $f[1] === '999');

        return [
            'id'       => $d->id,
            'casillas' => app(\App\Services\Formulario104Service::class)->paraElPortal($d->datos['casilleros'] ?? []),
            'comparar' => array_values($comparar),
        ];
    }
}
