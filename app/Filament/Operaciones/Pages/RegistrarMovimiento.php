<?php

namespace App\Filament\Operaciones\Pages;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\UbicacionAlmacen;
use App\Services\KardexService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Una pantalla. Sin pasos intermedios.
 *
 * Es la puerta por la que se mueve inventario a mano: todo pasa por
 * KardexService, así que aquí no se incrementa stock ni se calcula ningún
 * costo. El formulario solo decide el motivo y recoge lo que ese motivo exige
 * (costo en una entrada, acta en una baja, destino en un traslado).
 */
class RegistrarMovimiento extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Registrar movimiento';
    protected static ?string $title           = 'Registrar movimiento';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.operaciones.registrar-movimiento';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('inventario');
    }

    public static function getRoutePath(): string
    {
        return '/registrar-movimiento';
    }

    /** Los motivos que puede elegir una persona, agrupados por lo que hacen al saldo. */
    private const MOTIVOS = [
        'entrada'  => [
            'compra'          => 'Compra',
            'ajuste_sobrante' => 'Sobrante de conteo',
            'saldo_inicial'   => 'Saldo inicial',
        ],
        'salida'   => [
            'consumo_produccion' => 'Consumo a producción',
            'ajuste_faltante'    => 'Faltante de conteo',
            'baja'               => 'Baja por daño o caducidad',
        ],
        'traslado' => [
            'traslado' => 'Traslado entre ubicaciones',
        ],
    ];

    public function mount(): void
    {
        $this->form->fill([
            'tipo'   => request()->string('tipo')->toString() ?: 'entrada',
            'motivo' => request()->string('tipo')->toString() === 'salida' ? 'consumo_produccion' : 'compra',
            'item'   => request()->integer('item') ?: null,
            'fecha'  => now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\ToggleButtons::make('tipo')
                    ->label('Tipo de movimiento')
                    ->options([
                        'entrada'  => 'Entrada',
                        'salida'   => 'Salida',
                        'traslado' => 'Traslado entre ubicaciones',
                    ])
                    ->icons([
                        'entrada'  => 'heroicon-o-arrow-down-tray',
                        'salida'   => 'heroicon-o-arrow-up-tray',
                        'traslado' => 'heroicon-o-arrows-right-left',
                    ])
                    ->inline()
                    ->default('entrada')
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('motivo', array_key_first(self::MOTIVOS[$state] ?? ['compra' => ''])))
                    ->required(),

                Forms\Components\Select::make('motivo')
                    ->label('Motivo')
                    ->options(fn (Forms\Get $get) => self::MOTIVOS[$get('tipo')] ?? [])
                    ->required()
                    ->live()
                    ->helperText('El motivo decide el asiento contable y qué datos son obligatorios.'),

                Forms\Components\Select::make('item')
                    ->label('Ítem')
                    ->placeholder('Busca por nombre, código o escanea el código de barras')
                    ->options(fn () => InventoryItem::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()?->id)
                        ->where('activo', true)
                        ->orderBy('nombre')
                        ->get()
                        ->mapWithKeys(fn ($i) => [$i->id => "{$i->codigo} · {$i->nombre}"])
                        ->all())
                    ->searchable()
                    ->required()
                    ->live()
                    ->helperText(fn (Forms\Get $get) => $this->resumenDelItem($get('item'))),

                Forms\Components\TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(0.0001)
                    ->step('any')
                    ->required()
                    ->suffix(fn (Forms\Get $get) => InventoryItem::find($get('item'))?->measurementUnit?->abreviatura)
                    ->helperText('En la unidad de consumo del ítem, no en la de compra.'),

                Forms\Components\Select::make('almacen_id')
                    ->label(fn (Forms\Get $get) => $get('tipo') === 'traslado' ? 'Bodega' : 'Bodega destino')
                    ->options(fn () => \App\Models\Almacen::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()?->id)
                        ->where('activo', true)
                        ->pluck('nombre', 'id')
                        ->all())
                    ->searchable()
                    ->live()
                    ->required(),

                Forms\Components\Select::make('ubicacion_id')
                    ->label(fn (Forms\Get $get) => $get('tipo') === 'traslado' ? 'Ubicación de origen' : 'Ubicación destino')
                    ->options(fn (Forms\Get $get) => $this->ubicacionesDe($get('almacen_id')))
                    ->searchable()
                    ->helperText('La gaveta donde vive el producto.'),

                Forms\Components\Select::make('ubicacion_destino_id')
                    ->label('Ubicación de destino')
                    ->options(fn (Forms\Get $get) => $this->ubicacionesDe($get('almacen_id')))
                    ->searchable()
                    ->required(fn (Forms\Get $get) => $get('tipo') === 'traslado')
                    ->visible(fn (Forms\Get $get) => $get('tipo') === 'traslado'),

                Forms\Components\TextInput::make('costo_unitario')
                    ->label('Costo unitario')
                    ->numeric()
                    ->step('any')
                    ->prefix('$')
                    ->required(fn (Forms\Get $get) => $get('tipo') === 'entrada')
                    ->visible(fn (Forms\Get $get) => $get('tipo') === 'entrada')
                    ->helperText('Sin IVA. Lo que dice la factura del proveedor.'),

                Forms\Components\TextInput::make('documento')
                    ->label('Documento')
                    ->maxLength(120)
                    ->placeholder('COM-2026-0511, OP-2026-095, acta 014…')
                    ->helperText('Toda fila del kardex apunta a un documento.'),

                Forms\Components\DatePicker::make('fecha')
                    ->label('Fecha')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),

                Forms\Components\Fieldset::make('Acta de baja')
                    ->visible(fn (Forms\Get $get) => $get('motivo') === 'baja')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('acta_numero')
                            ->label('Número de acta')
                            ->required(fn (Forms\Get $get) => $get('motivo') === 'baja')
                            ->helperText('Sin acta el gasto por baja no es deducible ante el SRI.'),
                        Forms\Components\DatePicker::make('acta_fecha')
                            ->label('Fecha del acta')
                            ->required(fn (Forms\Get $get) => $get('motivo') === 'baja'),
                        Forms\Components\FileUpload::make('acta_archivo')
                            ->label('Acta escaneada')
                            ->directory('actas-baja')
                            ->acceptedFileTypes(['application/pdf', 'image/*']),
                    ]),
            ])
            ->columns(2)
            ->statePath('data');
    }

    /** Lo que hay hoy del ítem, para no registrar a ciegas. */
    private function resumenDelItem(?int $itemId): ?string
    {
        if (! $itemId || ! ($item = InventoryItem::withoutGlobalScopes()->find($itemId))) {
            return null;
        }

        $unidad = $item->measurementUnit?->abreviatura ?? '';

        return sprintf(
            'Hay %s %s · costo promedio $ %s · valor en bodega $ %s',
            rtrim(rtrim(number_format((float) $item->stock_actual, 4, ',', '.'), '0'), ','),
            $unidad,
            number_format((float) $item->costo_promedio, 4, ',', '.'),
            number_format((float) $item->saldo_valorado, 2, ',', '.'),
        );
    }

    /** @return array<int, string> */
    private function ubicacionesDe(?int $almacenId): array
    {
        if (! $almacenId) {
            return [];
        }

        return UbicacionAlmacen::withoutGlobalScopes()
            ->whereHas('zona', fn ($q) => $q->where('almacen_id', $almacenId))
            ->orderBy('codigo_ubicacion')
            ->pluck('codigo_ubicacion', 'id')
            ->all();
    }

    public function registrar(): void
    {
        $datos = $this->form->getState();

        $item = InventoryItem::withoutGlobalScopes()->findOrFail($datos['item']);

        abort_unless((int) $item->empresa_id === (int) Filament::getTenant()?->id, 403);

        try {
            $movimiento = app(KardexService::class)->registrar([
                'item'                 => $item,
                'motivo'               => $datos['motivo'],
                'cantidad'             => (float) $datos['cantidad'],
                'fecha'                => $datos['fecha'],
                'costo_unitario'       => (float) ($datos['costo_unitario'] ?? 0),
                'almacen_id'           => $datos['almacen_id'] ?? null,
                'ubicacion_id'         => $datos['ubicacion_id'] ?? null,
                'ubicacion_destino_id' => $datos['ubicacion_destino_id'] ?? null,
                'documento'            => $datos['documento'] ?? null,
                // Los ajustes por conteo llevan su asiento desde el observer.
                'referencia_tipo'      => in_array($datos['motivo'], ['ajuste_sobrante', 'ajuste_faltante'], true) ? 'ajuste' : $datos['motivo'],
                'acta'                 => [
                    'numero'  => $datos['acta_numero']  ?? null,
                    'fecha'   => $datos['acta_fecha']   ?? null,
                    'archivo' => $datos['acta_archivo'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se registró el movimiento')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Movimiento registrado')
            ->body(sprintf(
                'Quedan %s %s · costo promedio $ %s',
                rtrim(rtrim(number_format((float) $movimiento->saldo_cantidad, 4, ',', '.'), '0'), ','),
                $item->measurementUnit?->abreviatura ?? '',
                number_format((float) $movimiento->costo_promedio, 4, ',', '.'),
            ))
            ->success()
            ->send();

        $this->form->fill([
            'tipo'   => $datos['tipo'],
            'motivo' => $datos['motivo'],
            'fecha'  => $datos['fecha'],
        ]);
    }
}
