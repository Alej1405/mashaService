<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\Declaracion;
use App\Services\PosicionEmpresaService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * La posición de la empresa ante el SRI y la Superintendencia.
 *
 * Una pantalla, una tarea: responder qué vería un banco si pidiera los papeles
 * hoy. No se declara nada desde aquí ni se genera nada: se lee.
 */
class Posicion extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Posición de la empresa';
    protected static ?string $navigationGroup = 'Informes y cierre';
    protected static ?string $title           = 'Posición de la empresa';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.contabilidad.posicion';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    protected function getHeaderActions(): array
    {
        return [$this->accionCargarAnterior()];
    }

    /**
     * Sube una declaración presentada antes de usar el ERP.
     *
     * El PDF del SRI trae las fuentes con los glifos reordenados, así que su
     * texto no se puede leer sin reconstruir el mapa del documento. En lugar de
     * adivinarlo, se pide lo que alimenta los indicadores —cuatro campos— y el
     * PDF queda archivado como respaldo, que es para lo que sirve ante un banco.
     */
    private function accionCargarAnterior(): Action
    {
        return Action::make('cargar_anterior')
            ->label('Cargar una declaración presentada')
            ->icon('heroicon-o-document-arrow-up')
            ->color('primary')
            ->modalDescription('Para las declaraciones de antes de usar el ERP. Quedan en el '
                . 'histórico igual que las que genera el sistema.')
            ->form([
                Select::make('tipo')->label('Formulario')
                    ->options(Declaracion::TIPOS)->default('f104')->required(),

                Select::make('anio')->label('Año')
                    ->options(collect(range(now()->year, now()->year - 6))->mapWithKeys(fn ($a) => [$a => $a]))
                    ->default(now()->year)->required(),

                Select::make('mes')->label('Mes')
                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [
                        $m => Carbon::create(null, $m)->translatedFormat('F'),
                    ]))->default(now()->subMonth()->month)->required(),

                DatePicker::make('presentado_en')->label('Fecha de presentación')
                    ->maxDate(now())->required(),

                TextInput::make('comprobante_presentacion')->label('Número de serial')
                    ->helperText('El que aparece al pie del comprobante, junto al código verificador.')
                    ->maxLength(40),

                TextInput::make('ventas')->label('Total de ventas (casillero 419)')
                    ->numeric()->prefix('$')->default(0)
                    ->helperText('De aquí salen los indicadores de facturación.'),

                TextInput::make('valor_pagado')->label('Total pagado (casillero 999)')
                    ->numeric()->prefix('$')->default(0),

                FileUpload::make('archivo_pdf')->label('El comprobante en PDF')
                    ->acceptedFileTypes(['application/pdf'])->maxSize(5120)
                    ->disk('local')->directory('declaraciones/presentadas')
                    ->helperText('Se archiva como respaldo. Es lo que se enseña si lo piden.'),

                Placeholder::make('cierra')->label('')
                    ->content('El período NO se cierra al cargarla. Una declaración presentada antes '
                        . 'del ERP da historial y rastro del crédito tributario, pero el sistema no '
                        . 'tiene los movimientos que la sustentan: cerrar exige una declaración '
                        . 'generada aquí, con sus asientos detrás.'),
            ])
            ->action(function (array $data) {
                $empresa = Filament::getTenant();

                $existe = Declaracion::where('empresa_id', $empresa->id)
                    ->where('tipo', $data['tipo'])->where('anio', $data['anio'])
                    ->where('mes', $data['mes'])->whereNotNull('presentado_en')->first();

                if ($existe) {
                    Notification::make()->title('Ese período ya consta como presentado')
                        ->body('Si es una sustitutiva, cárgala como tal desde el período.')
                        ->warning()->persistent()->send();

                    return;
                }

                $declaracion = Declaracion::create([
                    'empresa_id'    => $empresa->id,
                    'tipo'          => $data['tipo'],
                    'origen'        => 'cargada',
                    'anio'          => (int) $data['anio'],
                    'mes'           => (int) $data['mes'],
                    'estado'        => 'listo',
                    'generado_en'   => $data['presentado_en'],
                    'presentado_en' => $data['presentado_en'],
                    'comprobante_presentacion' => $data['comprobante_presentacion'] ?? null,
                    'valor_pagado'  => $data['valor_pagado'] ?? 0,
                    'archivo_pdf'   => $data['archivo_pdf'] ?? null,
                    'solicitado_por' => auth()->id(),
                    // Los dos casilleros que alimentan la posición: lo demás vive
                    // en el PDF, que es el documento con valor probatorio.
                    'datos' => ['casilleros' => [
                        '419' => (float) ($data['ventas'] ?? 0),
                        '999' => (float) ($data['valor_pagado'] ?? 0),
                    ]],
                    'avisos' => ['Cargada a mano desde el comprobante presentado: los casilleros '
                        . 'de detalle no están, solo el total de ventas y lo pagado.'],
                ]);

                // Una declaración cargada NO cierra el mes: da trazabilidad del
                // crédito tributario, pero el sistema no tiene los movimientos
                // que la respaldan y no puede afirmar que cuadren.
                Notification::make()
                    ->title('Declaración cargada al histórico')
                    ->body("{$declaracion->periodo} entra en la posición de la empresa. El período "
                        . 'sigue abierto: solo lo cierra una declaración generada por el sistema, '
                        . 'que es la que tiene los asientos detrás.')
                    ->success()->persistent()->send();
            });
    }

    protected function getViewData(): array
    {
        return app(PosicionEmpresaService::class)->resumen(Filament::getTenant()->id);
    }
}
