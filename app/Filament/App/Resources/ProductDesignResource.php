<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ProductDesignResource\Pages;
use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\ProductDesign;
use App\Models\StoreCategory;
use Filament\Facades\Filament;
use Filament\Forms\Set;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductDesignResource extends Resource
{
    protected static ?string $model = ProductDesign::class;

    protected static ?string $tenantRelationshipName = 'productDesigns';

    protected static ?string $navigationIcon  = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Diseño de Productos';
    protected static ?string $navigationGroup = 'Diseño de Producto';
    protected static ?string $modelLabel      = 'Diseño de Producto';
    protected static ?string $pluralModelLabel = 'Diseño de Productos';

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make()
                ->tabs([
                    Tab::make('Información General')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            TextInput::make('nombre')
                                ->label('Nombre del Producto')
                                ->required()
                                ->maxLength(150)
                                ->columnSpan(2),
                            Select::make('store_category_id')
                                ->label('Categoría de Tienda')
                                ->options(fn () => StoreCategory::where('empresa_id', Filament::getTenant()->id)
                                    ->where('publicado', true)
                                    ->pluck('nombre', 'id'))
                                ->searchable()
                                ->nullable()
                                ->createOptionModalHeading('Nueva Categoría')
                                ->createOptionForm([
                                    TextInput::make('nombre')
                                        ->label('Nombre')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, ?string $state) =>
                                            $set('slug', \Illuminate\Support\Str::slug($state ?? ''))),
                                    TextInput::make('slug')
                                        ->label('Slug')
                                        ->required(),
                                    Toggle::make('publicado')
                                        ->label('Publicada')
                                        ->default(true),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    return StoreCategory::create([
                                        ...$data,
                                        'empresa_id' => Filament::getTenant()->id,
                                    ])->getKey();
                                })
                                ->columnSpan(1),
                            Toggle::make('activo')
                                ->label('Activo')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(1),
                            Toggle::make('tiene_multiples_presentaciones')
                                ->label('¿Tiene múltiples presentaciones?')
                                ->helperText('Ej: 250ml, 500ml, 1L. Si el producto es solo en una presentación, deja esto desactivado.')
                                ->default(false)
                                ->live()
                                ->inline(false)
                                ->columnSpan(2),
                            RichEditor::make('propuesta_valor')
                                ->label('Propuesta de Valor')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'h2', 'h3'])
                                ->columnSpanFull(),
                            Textarea::make('notas_estrategicas')
                                ->label('Notas Estratégicas')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])->columns(4),

                    Tab::make('Presentaciones y Fórmulas')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Repeater::make('presentations')
                                ->relationship()
                                ->label(fn (callable $get) => $get('tiene_multiples_presentaciones')
                                    ? 'Presentaciones del Producto'
                                    : 'Fórmula del Producto')
                                ->maxItems(fn (callable $get) => $get('tiene_multiples_presentaciones') ? null : 1)
                                ->addActionLabel(fn (callable $get) => $get('tiene_multiples_presentaciones')
                                    ? '+ Agregar presentación'
                                    : '+ Definir fórmula')
                                ->defaultItems(0)
                                ->schema([
                                    TextInput::make('nombre')
                                        ->label('Nombre / Tamaño')
                                        ->required(fn (callable $get) => (bool) $get('../../tiene_multiples_presentaciones'))
                                        ->placeholder('250ml, 500ml, Talla S, Unidad...')
                                        ->visible(fn (callable $get) => (bool) $get('../../tiene_multiples_presentaciones'))
                                        ->columnSpan(2),
                                    Select::make('measurement_unit_id')
                                        ->label('Unidad de Medida')
                                        ->options(fn () => MeasurementUnit::pluck('nombre', 'id'))
                                        ->searchable()
                                        ->createOptionModalHeading('Nueva Unidad de Medida')
                                        ->createOptionForm([
                                            TextInput::make('nombre')->label('Nombre')->required(),
                                            TextInput::make('abreviatura')->label('Abreviatura')->maxLength(10),
                                        ])
                                        ->createOptionUsing(fn (array $data): int =>
                                            MeasurementUnit::create([...$data, 'empresa_id' => Filament::getTenant()->id])->getKey())
                                        ->visible(fn (callable $get) => (bool) $get('../../tiene_multiples_presentaciones'))
                                        ->columnSpan(1),
                                    TextInput::make('cantidad_minima_produccion')
                                        ->label('Lote base')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->helperText('Unidades de referencia para la fórmula. Las órdenes de producción escalarán proporcionalmente.')
                                        ->columnSpan(fn (callable $get) => $get('../../tiene_multiples_presentaciones') ? 1 : 2),
                                    Toggle::make('activa')
                                        ->label('Activa')
                                        ->default(true)
                                        ->inline(false)
                                        ->visible(fn (callable $get) => (bool) $get('../../tiene_multiples_presentaciones'))
                                        ->columnSpan(1),

                                    Section::make('Fórmula / Insumos')
                                        ->description('Ingredientes o materiales necesarios para producir una unidad de esta presentación.')
                                        ->schema([
                                            Repeater::make('formulaLines')
                                                ->relationship()
                                                ->label('')
                                                ->schema([
                                                    // ── Insumo ──────────────────────────────────────────────
                                                    Select::make('inventory_item_id')
                                                        ->label('Insumo / Materia Prima')
                                                        ->options(fn () => InventoryItem::where('activo', true)
                                                            ->whereIn('type', ['insumo', 'materia_prima'])
                                                            ->get()
                                                            ->mapWithKeys(fn ($item) => [
                                                                $item->id => "{$item->codigo} — {$item->nombre}",
                                                            ]))
                                                        ->searchable()
                                                        ->nullable()
                                                        ->placeholder('Seleccionar insumo existente...')
                                                        ->live()
                                                        ->createOptionModalHeading('Nuevo Insumo / Materia Prima')
                                                        ->createOptionForm([
                                                            TextInput::make('nombre')
                                                                ->label('Nombre')
                                                                ->required()
                                                                ->maxLength(150)
                                                                ->columnSpanFull(),
                                                            Select::make('type')
                                                                ->label('Tipo')
                                                                ->options([
                                                                    'insumo'        => 'Insumo',
                                                                    'materia_prima' => 'Materia Prima',
                                                                ])
                                                                ->required(),
                                                            TextInput::make('purchase_price')
                                                                ->label('Precio de Compra (aprox.)')
                                                                ->numeric()
                                                                ->prefix('$')
                                                                ->required()
                                                                ->helperText('Puede ser un valor aproximado y ajustarlo luego.'),
                                                            Select::make('measurement_unit_id')
                                                                ->label('Unidad de Stock')
                                                                ->helperText('Ej: gramo (g), mililitro (ml), unidad...')
                                                                ->options(fn () => MeasurementUnit::pluck('nombre', 'id'))
                                                                ->searchable()
                                                                ->live()
                                                                ->required(),
                                                            Select::make('purchase_unit_id')
                                                                ->label('Unidad de Compra')
                                                                ->helperText('Ej: kilogramo (kg), litro (L)... Si compras y usas en la misma unidad, deja vacío.')
                                                                ->options(fn () => MeasurementUnit::pluck('nombre', 'id'))
                                                                ->searchable()
                                                                ->nullable()
                                                                ->live(),
                                                            TextInput::make('conversion_factor')
                                                                ->label('Factor de Conversión')
                                                                ->helperText('¿Cuántas unidades de stock hay en una unidad de compra? Ej: 1 kg = 1000 g → factor 1000')
                                                                ->numeric()
                                                                ->default(1)
                                                                ->required(),
                                                        ])
                                                        ->columns(2)
                                                        ->createOptionUsing(function (array $data): int {
                                                            return InventoryItem::create([
                                                                ...$data,
                                                                'empresa_id'  => Filament::getTenant()->id,
                                                                'activo'      => true,
                                                                'stock_actual' => 0,
                                                            ])->getKey();
                                                        })
                                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                            $item = InventoryItem::find($state);
                                                            if (!$item) {
                                                                $set('_factor', 1);
                                                                $set('_precio_compra', 0);
                                                                $set('_pu_label', '');
                                                                $set('_su_label', '');
                                                                $set('measurement_unit_id', null);
                                                                $set('costo_estimado', 0);
                                                                return;
                                                            }
                                                            $set('_factor', (float) ($item->conversion_factor ?? 1));
                                                            $set('_precio_compra', (float) $item->purchase_price);
                                                            $set('_pu_label', $item->purchaseUnit?->abreviatura ?? $item->measurementUnit?->abreviatura ?? '');
                                                            $set('_su_label', $item->measurementUnit?->abreviatura ?? '');
                                                            $set('measurement_unit_id', $item->measurement_unit_id);
                                                            self::calcularCostoLinea($get, $set);
                                                        })
                                                        ->columnSpan(4),

                                                    // ── Cantidad ────────────────────────────────────────────
                                                    TextInput::make('cantidad')
                                                        ->label('Cantidad')
                                                        ->numeric()
                                                        ->required()
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                            self::calcularCostoLinea($get, $set);
                                                        })
                                                        ->columnSpan(2),

                                                    // ── Unidad (auto-cargada del ítem, editable) ────────────
                                                    Select::make('measurement_unit_id')
                                                        ->label('Unidad')
                                                        ->options(fn () => MeasurementUnit::pluck('abreviatura', 'id'))
                                                        ->searchable()
                                                        ->createOptionModalHeading('Nueva Unidad')
                                                        ->createOptionForm([
                                                            TextInput::make('nombre')->label('Nombre')->required(),
                                                            TextInput::make('abreviatura')->label('Abreviatura')->maxLength(10),
                                                        ])
                                                        ->createOptionUsing(fn (array $data): int =>
                                                            MeasurementUnit::create([...$data, 'empresa_id' => Filament::getTenant()->id])->getKey())
                                                        ->columnSpan(2),

                                                    // ── Es subproducto ──────────────────────────────────────
                                                    Toggle::make('es_subproducto_manufacturado')
                                                        ->label('Subproducto')
                                                        ->inline(false)
                                                        ->columnSpan(1),

                                                    // ── Campos ocultos de conversión ────────────────────────
                                                    \Filament\Forms\Components\Hidden::make('_factor')->default(1),
                                                    \Filament\Forms\Components\Hidden::make('_precio_compra')->default(0),
                                                    \Filament\Forms\Components\Hidden::make('_pu_label')->default(''),
                                                    \Filament\Forms\Components\Hidden::make('_su_label')->default(''),
                                                    \Filament\Forms\Components\Hidden::make('costo_estimado')->default(0),

                                                    // ── Conversión de unidades (solo si hay factor ≠ 1) ─────
                                                    \Filament\Forms\Components\Placeholder::make('_conv_display')
                                                        ->label('Equivale en compra')
                                                        ->columnSpan(3)
                                                        ->visible(fn (callable $get) => (float) ($get('_factor') ?? 1) != 1.0 && (float) ($get('cantidad') ?? 0) > 0)
                                                        ->content(function (callable $get) {
                                                            $qty    = (float) ($get('cantidad') ?? 0);
                                                            $factor = (float) ($get('_factor') ?? 1);
                                                            $su     = $get('_su_label') ?: '?';
                                                            $pu     = $get('_pu_label') ?: '?';
                                                            $enCompra = $factor > 0 ? round($qty / $factor, 6) : 0;
                                                            return "{$qty} {$su}  =  " . number_format($enCompra, 4) . " {$pu}";
                                                        }),

                                                    // ── Costo estimado ──────────────────────────────────────
                                                    \Filament\Forms\Components\Placeholder::make('_costo_display')
                                                        ->label('Costo estimado')
                                                        ->columnSpan(3)
                                                        ->visible(fn (callable $get) => (float) ($get('_precio_compra') ?? 0) > 0)
                                                        ->content(function (callable $get) {
                                                            $qty          = (float) ($get('cantidad') ?? 0);
                                                            $precioCompra = (float) ($get('_precio_compra') ?? 0);
                                                            $factor       = max((float) ($get('_factor') ?? 1), 0.000001);
                                                            $pu           = $get('_pu_label') ?: '?';
                                                            $su           = $get('_su_label') ?: '?';
                                                            // Fórmula: (precio_compra ÷ factor) × cantidad
                                                            $precioStock  = $precioCompra / $factor;
                                                            $costo        = round($qty * $precioStock, 2);
                                                            return '$ ' . number_format($costo, 2)
                                                                . "  ($" . number_format($precioCompra, 2) . " / {$factor} {$su}/{$pu}"
                                                                . " × {$qty} {$su})";
                                                        }),

                                                    // ── Notas ───────────────────────────────────────────────
                                                    Textarea::make('notas')
                                                        ->label('Notas')
                                                        ->rows(2)
                                                        ->columnSpanFull(),
                                                ])
                                                ->columns(12)
                                                ->addActionLabel('+ Agregar insumo')
                                                ->defaultItems(0),
                                        ])
                                        ->collapsible()
                                        ->columnSpanFull(),

                                ])
                                ->columns(6)
                                ->defaultItems(0)
                                ->itemLabel(function (array $state, callable $get): string {
                                    if (!$get('tiene_multiples_presentaciones')) {
                                        return 'Fórmula';
                                    }
                                    return $state['nombre'] ?? 'Nueva presentación';
                                })
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Proceso y Capacidad')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Repeater::make('productionSteps')
                                ->relationship()
                                ->label('Pasos del Proceso')
                                ->schema([
                                    TextInput::make('orden')
                                        ->label('Orden')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(1),
                                    TextInput::make('nombre')
                                        ->label('Nombre del Paso')
                                        ->required()
                                        ->placeholder('Mezclado, Envasado, Etiquetado...')
                                        ->columnSpan(4),
                                    TextInput::make('tiempo_estimado_minutos')
                                        ->label('Tiempo (min)')
                                        ->numeric()
                                        ->suffix('min')
                                        ->columnSpan(2),
                                    Textarea::make('descripcion')
                                        ->label('Descripción del Paso')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(7)
                                ->addActionLabel('+ Agregar paso')
                                ->defaultItems(0)
                                ->reorderable('orden')
                                ->orderColumn('orden')
                                ->columnSpanFull(),

                            // ── Capacidad instalada ──────────────────────────────
                            \Filament\Forms\Components\Section::make('Capacidad de Producción')
                                ->description('Define cuánto puedes producir al mes y el sistema calcula la capacidad diaria.')
                                ->columns(4)
                                ->schema([
                                    TextInput::make('capacidad_instalada_mensual')
                                        ->label('Capacidad Instalada Mensual (unidades)')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->placeholder('Ej: 1200')
                                        ->columnSpan(2),
                                    TextInput::make('dias_laborales_mes')
                                        ->label('Días Laborales / Mes')
                                        ->numeric()
                                        ->default(22)
                                        ->live(onBlur: true)
                                        ->suffix('días')
                                        ->columnSpan(1),
                                    \Filament\Forms\Components\Placeholder::make('_capacidad_diaria')
                                        ->label('Capacidad Diaria')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $mensual = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                            $dias    = max((int) ($get('dias_laborales_mes') ?? 22), 1);
                                            if ($mensual <= 0) return '—';
                                            $diaria = $mensual / $dias;
                                            return new \Illuminate\Support\HtmlString(
                                                '<span style="font-size:1.1rem;font-weight:bold">'
                                                . number_format($diaria, 2) . ' u/día</span>'
                                            );
                                        }),
                                ]),

                            // ── Mano de obra ─────────────────────────────────────
                            \Filament\Forms\Components\Section::make('Mano de Obra')
                                ->description('Personas necesarias para cubrir la capacidad instalada mensual.')
                                ->columns(4)
                                ->schema([
                                    TextInput::make('num_personas')
                                        ->label('Número de Personas')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->suffix('personas')
                                        ->columnSpan(1),
                                    TextInput::make('costo_mano_obra_persona')
                                        ->label('Costo Mensual por Persona')
                                        ->numeric()
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->columnSpan(1),
                                    \Filament\Forms\Components\Placeholder::make('_total_mano_obra')
                                        ->label('Total Mano de Obra / Mes')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $personas = (float) ($get('num_personas') ?? 0);
                                            $costo    = (float) ($get('costo_mano_obra_persona') ?? 0);
                                            $total    = $personas * $costo;
                                            return new \Illuminate\Support\HtmlString(
                                                '<span style="font-size:1.1rem;font-weight:bold">$ ' . number_format($total, 2) . '</span>'
                                            );
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('_costo_mo_por_unidad')
                                        ->label('Mano de Obra por Unidad')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $personas  = (float) ($get('num_personas') ?? 0);
                                            $costo     = (float) ($get('costo_mano_obra_persona') ?? 0);
                                            $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                            if ($capacidad <= 0) return '—';
                                            $porUnidad = ($personas * $costo) / $capacidad;
                                            return new \Illuminate\Support\HtmlString(
                                                '<span style="font-size:1.1rem;font-weight:bold">$ ' . number_format($porUnidad, 4) . '</span>'
                                            );
                                        }),
                                ]),
                        ]),

                    Tab::make('Costos Operativos')
                        ->icon('heroicon-o-calculator')
                        ->schema([

                            // ── Otras inversiones ────────────────────────────────
                            \Filament\Forms\Components\Section::make('Otras Inversiones')
                                ->description('Gastos mensuales adicionales: diseño de marca, publicidad, etc.')
                                ->schema([
                                    Repeater::make('indirectCosts')
                                        ->relationship()
                                        ->label('')
                                        ->schema([
                                            \Filament\Forms\Components\Select::make('tipo')
                                                ->label('Tipo')
                                                ->options([
                                                    'diseño_marca' => 'Diseño de Marca',
                                                    'publicidad'   => 'Publicidad',
                                                    'logistica'    => 'Logística',
                                                    'arriendo'     => 'Arriendo',
                                                    'servicios'    => 'Servicios (luz, agua, etc.)',
                                                    'otro'         => 'Otro',
                                                ])
                                                ->required()
                                                ->live()
                                                ->columnSpan(2),
                                            TextInput::make('descripcion')
                                                ->label('Descripción')
                                                ->placeholder('Detalle opcional...')
                                                ->columnSpan(3),
                                            TextInput::make('monto_mensual')
                                                ->label('Monto')
                                                ->numeric()
                                                ->prefix('$')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->columnSpan(2),
                                            \Filament\Forms\Components\Radio::make('frecuencia')
                                                ->label('Frecuencia')
                                                ->options([
                                                    'semanal'  => 'Semanal',
                                                    'mensual'  => 'Mensual',
                                                    'unico'    => 'Un solo pago',
                                                ])
                                                ->default('mensual')
                                                ->inline()
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(7)
                                        ->addActionLabel('+ Agregar inversión')
                                        ->defaultItems(0)
                                        ->itemLabel(fn (array $state): ?string =>
                                            match ($state['tipo'] ?? null) {
                                                'diseño_marca' => 'Diseño de Marca',
                                                'publicidad'   => 'Publicidad',
                                                'logistica'    => 'Logística',
                                                'arriendo'     => 'Arriendo',
                                                'servicios'    => 'Servicios',
                                                'otro'         => 'Otro',
                                                default        => null,
                                            }
                                            . (isset($state['monto_mensual']) && $state['monto_mensual'] > 0
                                                ? '  —  $ ' . number_format((float) $state['monto_mensual'], 2)
                                                : '')
                                            . (isset($state['frecuencia'])
                                                ? '  (' . match ($state['frecuencia']) {
                                                    'semanal' => 'Semanal',
                                                    'mensual' => 'Mensual',
                                                    'unico'   => 'Un solo pago',
                                                    default   => '',
                                                } . ')'
                                                : '')
                                        )
                                        ->collapsible()
                                        ->columnSpanFull(),
                                ]),

                            // ── Resumen de costos indirectos ─────────────────────
                            \Filament\Forms\Components\Section::make('Resumen de Costos Indirectos')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('_tabla_resumen_indirectos')
                                        ->label('')
                                        ->columnSpanFull()
                                        ->content(function (callable $get) {
                                            $capacidad  = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                            $personas   = (float) ($get('num_personas') ?? 0);
                                            $costoMo    = (float) ($get('costo_mano_obra_persona') ?? 0);
                                            $totalMo    = $personas * $costoMo;
                                            $otros      = $get('indirectCosts') ?? [];

                                            $frecLabel = fn (string $f): string => match ($f) {
                                                'semanal' => 'Semanal',
                                                'mensual' => 'Mensual',
                                                'unico'   => 'Un solo pago',
                                                default   => '—',
                                            };

                                            $rows      = '';
                                            $totalMes  = 0;

                                            // Fila mano de obra
                                            if ($totalMo > 0) {
                                                $porUnidad = $capacidad > 0 ? $totalMo / $capacidad : 0;
                                                $totalMes += $totalMo;
                                                $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                                    <td class='py-1 pr-4 text-sm'>Mano de Obra</td>
                                                    <td class='py-1 pr-4 text-sm text-gray-400'>{$personas} persona(s) × $ " . number_format($costoMo, 2) . "</td>
                                                    <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700'>Mensual</span></td>
                                                    <td class='py-1 pr-4 text-sm text-right font-mono'>$ " . number_format($totalMo, 2) . "</td>
                                                    <td class='py-1 text-sm text-right font-mono'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidad, 4) : '—') . "</td>
                                                </tr>";
                                            }

                                            // Filas otras inversiones
                                            foreach ($otros as $item) {
                                                $monto     = (float) ($item['monto_mensual'] ?? 0);
                                                $frec      = $item['frecuencia'] ?? 'mensual';
                                                $tipo      = match ($item['tipo'] ?? '') {
                                                    'diseño_marca' => 'Diseño de Marca',
                                                    'publicidad'   => 'Publicidad',
                                                    'logistica'    => 'Logística',
                                                    'arriendo'     => 'Arriendo',
                                                    'servicios'    => 'Servicios',
                                                    'otro'         => 'Otro',
                                                    default        => '—',
                                                };
                                                $desc      = $item['descripcion'] ?? '';
                                                $nombre    = $desc ? "{$tipo} — {$desc}" : $tipo;
                                                $totalMes += $monto;
                                                $porUnidad = $capacidad > 0 ? $monto / $capacidad : 0;
                                                $badgeColor = match ($frec) {
                                                    'semanal' => 'bg-amber-100 text-amber-700',
                                                    'unico'   => 'bg-purple-100 text-purple-700',
                                                    default   => 'bg-blue-100 text-blue-700',
                                                };
                                                $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                                    <td class='py-1 pr-4 text-sm'>{$nombre}</td>
                                                    <td class='py-1 pr-4 text-sm text-gray-400'>—</td>
                                                    <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs {$badgeColor}'>" . $frecLabel($frec) . "</span></td>
                                                    <td class='py-1 pr-4 text-sm text-right font-mono'>$ " . number_format($monto, 2) . "</td>
                                                    <td class='py-1 text-sm text-right font-mono'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidad, 4) : '—') . "</td>
                                                </tr>";
                                            }

                                            // Costos fijos de empresa
                                            $totalFijosMes = self::costosFijosMensuales();
                                            if ($totalFijosMes > 0) {
                                                $porUnidadFijos = $capacidad > 0 ? $totalFijosMes / $capacidad : 0;
                                                $totalMes += $totalFijosMes;
                                                $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700' style='background:rgba(124,58,237,0.05);'>
                                                    <td class='py-1 pr-4 text-sm' style='color:#7c3aed;'>🏢 Costos Fijos Empresa</td>
                                                    <td class='py-1 pr-4 text-sm text-gray-400'>Prorrateo mensual</td>
                                                    <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700'>Mensual</span></td>
                                                    <td class='py-1 pr-4 text-sm text-right font-mono' style='color:#7c3aed;'>$ " . number_format($totalFijosMes, 2) . "</td>
                                                    <td class='py-1 text-sm text-right font-mono' style='color:#7c3aed;'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidadFijos, 4) : '—') . "</td>
                                                </tr>";
                                            }

                                            if (empty($rows)) {
                                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Sin costos indirectos registrados.</p>');
                                            }

                                            $porUnidadTotal = $capacidad > 0 ? $totalMes / $capacidad : 0;

                                            // Fila de total
                                            $rows .= "<tr class='border-t-2 border-gray-300 dark:border-gray-500 font-bold'>
                                                <td class='py-2 pr-4 text-sm' colspan='3'>Total (Indirectos + Fijos) / Mes</td>
                                                <td class='py-2 pr-4 text-sm text-right font-mono'>$ " . number_format($totalMes, 2) . "</td>
                                                <td class='py-2 text-sm text-right font-mono'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidadTotal, 4) : '—') . "</td>
                                            </tr>";

                                            // Filas costo total real por presentación (materiales + indirectos + fijos)
                                            $presentations = $get('presentations') ?? [];
                                            $pIdx = 0;
                                            foreach ($presentations as $pKey => $pres) {
                                                $pIdx++;
                                                $presNombre    = $pres['nombre'] ?? ('Presentación ' . $pIdx);
                                                $costoMat      = self::costoLote($pres['formulaLines'] ?? []);
                                                $lote          = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                                $matPorUnidad  = $costoMat / $lote;
                                                $costoTotal    = $matPorUnidad + $porUnidadTotal;
                                                $rows .= "<tr class='border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800'>
                                                    <td class='py-2 pr-4 text-sm font-semibold' colspan='2'>" . e($presNombre) . " — Costo Total / Unidad</td>
                                                    <td class='py-2 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs bg-green-100 text-green-700'>Mat: $ " . number_format($matPorUnidad, 4) . "</span></td>
                                                    <td class='py-2 pr-4 text-sm text-right font-mono text-gray-400'>Ind+Fijos: $ " . number_format($porUnidadTotal, 4) . "</td>
                                                    <td class='py-2 text-sm text-right font-mono font-bold' style='color:#dc2626'>$ " . number_format($costoTotal, 4) . "</td>
                                                </tr>";
                                            }

                                            return new \Illuminate\Support\HtmlString("
                                                <div class='overflow-x-auto'>
                                                    <table class='w-full'>
                                                        <thead>
                                                            <tr class='border-b border-gray-200 dark:border-gray-600'>
                                                                <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Concepto</th>
                                                                <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Detalle</th>
                                                                <th class='pb-1 pr-4 text-center text-xs font-semibold text-gray-500 uppercase'>Frecuencia</th>
                                                                <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Monto / Mes</th>
                                                                <th class='pb-1 text-right text-xs font-semibold text-gray-500 uppercase'>Por Unidad</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>{$rows}</tbody>
                                                    </table>
                                                    <p class='mt-2 text-xs text-gray-400'>* La coma (,) es separador de miles. Ej: 1,000 = mil.</p>
                                                </div>
                                            ");
                                        }),
                                ]),
                        ]),

                    Tab::make('Estrategia Comercial')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([

                            // ── Precio de venta e impuestos ──────────────────────
                            \Filament\Forms\Components\Section::make('Precio de Venta e Impuestos')
                                ->description('Define el precio de venta y si aplican impuestos especiales. El IVA (15%) aplica siempre como passthrough.')
                                ->columns(4)
                                ->schema([
                                    TextInput::make('_plan_margen_venta')
                                        ->label('Margen de Utilidad (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $costo = self::planCostoUnitario($get);
                                            if ($costo <= 0) return;
                                            $margen  = (float) ($state ?? 0);
                                            $divisor = 1 - ($margen / 100);
                                            if ($divisor <= 0) return;
                                            $pvpSinIva  = round($costo / $divisor, 2);
                                            $incluyeIva = (bool) ($get('_plan_pvp_incluye_iva') ?? false);
                                            $set('_plan_pvp_venta', $incluyeIva ? round($pvpSinIva * 1.15, 2) : $pvpSinIva);
                                        })
                                        ->placeholder('Ej: 40')
                                        ->helperText('Ingresa el margen → se calcula el PVP.')
                                        ->columnSpan(1),

                                    TextInput::make('_plan_pvp_venta')
                                        ->label(fn (callable $get) => (bool) ($get('_plan_pvp_incluye_iva') ?? false)
                                            ? 'PVP de Venta (con IVA incluido)'
                                            : 'PVP de Venta (sin IVA)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $costo = self::planCostoUnitario($get);
                                            if ($costo <= 0) return;
                                            $pvp = (float) ($state ?? 0);
                                            if ($pvp <= 0) return;
                                            $incluyeIva = (bool) ($get('_plan_pvp_incluye_iva') ?? false);
                                            $pvpSinIva  = $incluyeIva ? $pvp / 1.15 : $pvp;
                                            $set('_plan_margen_venta', round((($pvpSinIva - $costo) / $pvpSinIva) * 100, 2));
                                            // Actualizar precio distribuidor con el nuevo PVP
                                            $margenDist = (float) ($get('margen_distribuidor') ?: 40);
                                            $set('precio_distribuidor', round($pvpSinIva * (1 - $margenDist / 100), 2));
                                        })
                                        ->placeholder('Ej: 2.50')
                                        ->helperText('Ingresa el PVP → se calcula el margen.')
                                        ->columnSpan(1),

                                    Toggle::make('_plan_pvp_incluye_iva')
                                        ->label('¿El PVP ya incluye IVA (15%)?')
                                        ->dehydrated(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $pvp = (float) ($get('_plan_pvp_venta') ?? 0);
                                            if ($pvp <= 0) return;
                                            $incluyeIva = (bool) $state;
                                            $pvpSinIva  = $incluyeIva ? round($pvp / 1.15, 4) : $pvp;
                                            // Recalcular margen de venta
                                            $costo = self::planCostoUnitario($get);
                                            if ($costo > 0 && $pvpSinIva > 0) {
                                                $set('_plan_margen_venta', round((($pvpSinIva - $costo) / $pvpSinIva) * 100, 2));
                                            }
                                            // Recalcular precio distribuidor
                                            $margenDist = (float) ($get('margen_distribuidor') ?: 40);
                                            $set('precio_distribuidor', round($pvpSinIva * (1 - $margenDist / 100), 2));
                                        })
                                        ->helperText('Actívalo si el precio ingresado es el precio final al consumidor con IVA incluido.')
                                        ->columnSpan(2),

                                    TextInput::make('_plan_dias_venta')
                                        ->label('Días estimados para vender')
                                        ->numeric()
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->suffix('días')
                                        ->placeholder('Ej: 30')
                                        ->helperText('¿En cuántos días esperas vender toda la producción?')
                                        ->columnSpan(1),

                                    TextInput::make('_plan_meta_ganancia')
                                        ->label('Meta de Rentabilidad (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->default(5)
                                        ->placeholder('5')
                                        ->helperText('ROI mínimo esperado.')
                                        ->columnSpan(1),

                                    Toggle::make('_plan_aplica_ice')
                                        ->label('¿El producto paga ICE?')
                                        ->dehydrated(false)
                                        ->live()
                                        ->helperText('Impuesto a los Consumos Especiales — SRI Ecuador')
                                        ->columnSpan(2),

                                    Select::make('_plan_ice_categoria')
                                        ->label('Categoría ICE según la LRTI')
                                        ->options([
                                            'cerveza_artesanal' => 'Cerveza artesanal (prod. ≤ 50.000 l/año)',
                                            'cerveza'           => 'Cerveza industrial',
                                            'vino'              => 'Vino / Bebidas fermentadas naturales',
                                            'licor_bajo'        => 'Bebida alcohólica / Licor  < 20° GL',
                                            'licor_medio'       => 'Bebida alcohólica / Licor  20–50° GL',
                                            'licor_alto'        => 'Bebida alcohólica / Licor  > 50° GL',
                                            'otro'              => 'Otro producto con ICE (tasa manual)',
                                        ])
                                        ->dehydrated(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $tasas = [
                                                'cerveza_artesanal' => 30,
                                                'cerveza'           => 75,
                                                'vino'              => 40,
                                                'licor_bajo'        => 75,
                                                'licor_medio'       => 100,
                                                'licor_alto'        => 100,
                                                'otro'              => 0,
                                            ];
                                            $set('_plan_ice_porcentaje', $tasas[$state] ?? 0);
                                        })
                                        ->visible(fn (callable $get) => (bool) $get('_plan_aplica_ice'))
                                        ->columnSpan(1),

                                    TextInput::make('_plan_ice_porcentaje')
                                        ->label('Tasa ICE (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->helperText('% sobre el precio ex-fábrica (PVP sin IVA). Verifique en la LRTI.')
                                        ->visible(fn (callable $get) => (bool) $get('_plan_aplica_ice'))
                                        ->columnSpan(1),

                                    \Filament\Forms\Components\Placeholder::make('_plan_ice_aviso')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div style="border-radius:0.5rem;background:#fffbeb;border:1px solid #fde68a;padding:0.6rem 0.9rem;font-size:0.75rem;color:#92400e;">'
                                            . '<strong>⚠ Nota:</strong> Tasas referenciales según la LRTI. Verifique con el SRI las tarifas vigentes y la base de cálculo aplicable a su producto.'
                                            . '</div>'
                                        ))
                                        ->visible(fn (callable $get) => (bool) $get('_plan_aplica_ice'))
                                        ->columnSpan(2),
                                ]),

                            // ── Canal Distribuidores ────────────────────────────
                            \Filament\Forms\Components\Section::make('Canal Distribuidores')
                                ->description('Configura el precio y las condiciones mínimas para el canal de distribuidores.')
                                ->columns(4)
                                ->schema([
                                    TextInput::make('margen_distribuidor')
                                        ->label('Margen Distribuidor (%)')
                                        ->numeric()
                                        ->default(40)
                                        ->suffix('%')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $margen     = (float) $state;
                                            $pvp        = (float) ($get('_plan_pvp_venta') ?? 0);
                                            $incluyeIva = (bool) ($get('_plan_pvp_incluye_iva') ?? false);
                                            $pvpSinIva  = ($pvp > 0 && $incluyeIva) ? $pvp / 1.15 : $pvp;
                                            if ($pvpSinIva > 0) {
                                                $set('precio_distribuidor', round($pvpSinIva * (1 - $margen / 100), 2));
                                            }
                                        })
                                        ->helperText('Ingresa el margen → calcula el precio distribuidor.')
                                        ->columnSpan(1),

                                    TextInput::make('precio_distribuidor')
                                        ->label('Precio Distribuidor')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $precio     = (float) $state;
                                            $pvp        = (float) ($get('_plan_pvp_venta') ?? 0);
                                            $incluyeIva = (bool) ($get('_plan_pvp_incluye_iva') ?? false);
                                            $pvpSinIva  = ($pvp > 0 && $incluyeIva) ? $pvp / 1.15 : $pvp;
                                            if ($pvpSinIva > 0 && $precio >= 0) {
                                                $set('margen_distribuidor', round((1 - $precio / $pvpSinIva) * 100, 2));
                                            }
                                        })
                                        ->helperText('Ingresa el precio → calcula el margen.')
                                        ->columnSpan(1),

                                    TextInput::make('cantidad_minima_distribuidor')
                                        ->label('Cantidad mínima (precio dist.)')
                                        ->numeric()
                                        ->default(10)
                                        ->minValue(1)
                                        ->helperText('Unidades mínimas en tienda para aplicar precio de distribuidor.')
                                        ->columnSpan(1),

                                    \Filament\Forms\Components\Placeholder::make('_dist_info')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<p style="font-size:0.72rem;color:#6b7280;line-height:1.5;">'
                                            . 'El precio de distribuidor y la cantidad mínima se aplicarán automáticamente en la tienda online cuando un cliente compre la cantidad mínima o más unidades de este producto.'
                                            . '</p>'
                                        ))
                                        ->columnSpan(1),
                                ]),
                        ]),
                    Tab::make('Simulación y Análisis')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([

                            // ── Alerta de capacidad vs simulaciones activas ────
                            \Filament\Forms\Components\Placeholder::make('_alerta_capacidad_sim')
                                ->label('')
                                ->columnSpanFull()
                                ->content(function (callable $get, $record) {
                                    $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                    $personas  = (float) ($get('num_personas') ?? 0);
                                    $costoMo   = (float) ($get('costo_mano_obra_persona') ?? 0);
                                    $diasLab   = max((int) ($get('dias_laborales_mes') ?? 22), 1);

                                    if (!$record || $capacidad <= 0) return '';

                                    $sims = \App\Models\ProductSimulation::where('product_design_id', $record->id)
                                        ->where('estado', 'en_proyecto')
                                        ->get();

                                    if ($sims->isEmpty()) return '';

                                    $totalDemanda = $sims->sum('cantidad');
                                    $pctUso = ($totalDemanda / $capacidad) * 100;

                                    if ($totalDemanda <= $capacidad) {
                                        $disponible = $capacidad - $totalDemanda;
                                        return new \Illuminate\Support\HtmlString(
                                            '<div style="padding:0.75rem 1rem;border-radius:0.75rem;background:#f0fdf4;border:1px solid #bbf7d0;margin-bottom:0.5rem;">'
                                            . '<div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">'
                                            . '<span style="font-size:0.78rem;font-weight:700;color:#15803d;">✓ Capacidad OK</span>'
                                            . '<span style="font-size:0.72rem;color:#374151;">' . $sims->count() . ' simulación(es) en proyecto · <strong>' . number_format($totalDemanda, 0) . '</strong> de ' . number_format($capacidad, 0) . ' u/mes (<strong>' . number_format($pctUso, 1) . '%</strong>) · Disponible: <strong>' . number_format($disponible, 0) . ' u.</strong></span>'
                                            . '</div>'
                                            . self::renderListaSimulaciones($sims)
                                            . '</div>'
                                        );
                                    }

                                    // ── Excede capacidad ──
                                    $exceso = $totalDemanda - $capacidad;
                                    $capPorPersona = $personas > 0 ? $capacidad / $personas : $capacidad;
                                    $personasExtra = $capPorPersona > 0 ? ceil($exceso / $capPorPersona) : 0;
                                    $costoExtra    = $personasExtra * $costoMo;
                                    $totalPersonas = $personas + $personasExtra;
                                    $totalCostoMo  = $totalPersonas * $costoMo;
                                    $nuevaCapacidad = $capacidad + ($personasExtra * $capPorPersona);
                                    $diaria = $capacidad / $diasLab;
                                    $diasActual = $diaria > 0 ? ceil($totalDemanda / $diaria) : 0;
                                    $diariaAmpliada = $nuevaCapacidad / $diasLab;
                                    $diasAmpliado = $diariaAmpliada > 0 ? ceil($totalDemanda / $diariaAmpliada) : 0;
                                    $fmt = fn ($v) => number_format((float) $v, 2);

                                    $html = '<div style="padding:1.25rem 1.5rem;border-radius:0.75rem;background:linear-gradient(135deg,#fef2f2 0%,#fff7ed 100%);border:2px solid #fca5a5;margin-bottom:0.5rem;">';
                                    $html .= '<p style="font-weight:700;color:#dc2626;font-size:0.9rem;margin-bottom:0.25rem;">⚠️ Capacidad insuficiente para las simulaciones activas</p>';
                                    $html .= '<p style="font-size:0.75rem;color:#6b7280;margin-bottom:1rem;">Si se ejecutan todas las simulaciones en proyecto simultáneamente, la demanda supera tu capacidad instalada.</p>';

                                    // KPIs
                                    $html .= '<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:0.75rem;margin-bottom:1.25rem;">';
                                    $kpi = function (string $label, string $value, string $sub, string $color) {
                                        return '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.6rem;padding:0.75rem;text-align:center;">'
                                            . '<p style="font-size:0.6rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.15rem;">' . $label . '</p>'
                                            . '<p style="font-size:1.3rem;font-weight:800;color:' . $color . ';line-height:1.1;">' . $value . '</p>'
                                            . '<p style="font-size:0.6rem;color:#9ca3af;margin-top:0.1rem;">' . $sub . '</p>'
                                            . '</div>';
                                    };
                                    $html .= $kpi('Demanda Total', number_format($totalDemanda, 0) . ' u.', $sims->count() . ' simulaciones activas', '#dc2626');
                                    $html .= $kpi('Capacidad Actual', number_format($capacidad, 0) . ' u.', $personas > 0 ? $personas . ' persona(s)' : 'Sin personal', '#6b7280');
                                    $html .= $kpi('Exceso', number_format($exceso, 0) . ' u.', number_format($pctUso - 100, 1) . '% sobre capacidad', '#f59e0b');
                                    $html .= $kpi('Personal Adicional', '+' . $personasExtra, 'Total: ' . $totalPersonas . ' personas', '#2563eb');
                                    $html .= $kpi('Costo Adicional M.O.', '$ ' . $fmt($costoExtra), 'Total: $ ' . $fmt($totalCostoMo) . '/mes', '#7c3aed');
                                    $html .= '</div>';

                                    // Comparativa
                                    $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">';
                                    $html .= '<div style="padding:0.75rem 1rem;background:#fff;border:1px solid #fca5a5;border-radius:0.5rem;">'
                                        . '<p style="font-size:0.72rem;font-weight:700;color:#dc2626;margin-bottom:0.5rem;">❌ Escenario Actual</p>'
                                        . '<p style="font-size:0.7rem;color:#374151;">Capacidad: <strong>' . number_format($capacidad, 0) . ' u/mes</strong> · Personal: <strong>' . $personas . '</strong> · M.O.: <strong>$ ' . $fmt($personas * $costoMo) . '/mes</strong></p>'
                                        . '<p style="font-size:0.7rem;color:#dc2626;">Tiempo para cubrir demanda: <strong>' . $diasActual . ' días háb.</strong> (' . round($diasActual / $diasLab, 1) . ' meses)</p>'
                                        . '</div>';
                                    $html .= '<div style="padding:0.75rem 1rem;background:#fff;border:1px solid #bbf7d0;border-radius:0.5rem;">'
                                        . '<p style="font-size:0.72rem;font-weight:700;color:#16a34a;margin-bottom:0.5rem;">✓ Escenario Ampliado</p>'
                                        . '<p style="font-size:0.7rem;color:#374151;">Capacidad: <strong>' . number_format($nuevaCapacidad, 0) . ' u/mes</strong> · Personal: <strong>' . $totalPersonas . '</strong> · M.O.: <strong>$ ' . $fmt($totalCostoMo) . '/mes</strong></p>'
                                        . '<p style="font-size:0.7rem;color:#16a34a;">Tiempo para cubrir demanda: <strong>' . $diasAmpliado . ' días háb.</strong> (' . round($diasAmpliado / $diasLab, 1) . ' meses)</p>'
                                        . '</div>';
                                    $html .= '</div>';

                                    $html .= self::renderListaSimulaciones($sims);
                                    $html .= '</div>';
                                    return new \Illuminate\Support\HtmlString($html);
                                }),

                            // ── Entradas del plan ────────────────────────────────
                            \Filament\Forms\Components\Section::make('Parámetros de Simulación')
                                ->description('Ajusta los valores para simular diferentes escenarios sin afectar los datos guardados.')
                                ->columns(3)
                                ->schema([

                                    // ── Cargar simulación guardada ────────────────
                                    Select::make('_plan_cargar_simulacion')
                                        ->label('📂 Cargar simulación guardada')
                                        ->placeholder('Seleccionar simulación...')
                                        ->dehydrated(false)
                                        ->live()
                                        ->columnSpanFull()
                                        ->options(function (callable $get, $record) {
                                            if (!$record) return [];
                                            return \App\Models\ProductSimulation::where('product_design_id', $record->id)
                                                ->orderByDesc('created_at')
                                                ->get()
                                                ->mapWithKeys(fn ($s) => [
                                                    $s->id => $s->nombre
                                                        . '  ·  ' . number_format($s->cantidad, 0) . ' u.'
                                                        . '  ·  PVP $ ' . number_format($s->pvp_sin_iva, 2)
                                                        . '  ·  ' . $s->created_at->format('d/m/Y'),
                                                ])
                                                ->toArray();
                                        })
                                        ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                                            if (!$state) return;
                                            $sim = \App\Models\ProductSimulation::find($state);
                                            if (!$sim) return;

                                            // Buscar el índice de la presentación por nombre
                                            $presentations = $get('presentations') ?? [];
                                            $presKey = null;
                                            foreach ($presentations as $key => $p) {
                                                if (($p['nombre'] ?? '') === $sim->presentation_nombre) {
                                                    $presKey = $key;
                                                    break;
                                                }
                                            }

                                            if ($presKey !== null) $set('_plan_presentation_id', $presKey);
                                            $set('_plan_cantidad',           $sim->cantidad);
                                            $set('_plan_pvp_venta',          $sim->pvp_sin_iva);
                                            $set('_plan_margen_venta',       $sim->margen_porcentaje);
                                            $set('_plan_dias_venta',         $sim->dias_venta);
                                            $set('_plan_meta_ganancia',      $sim->meta_ganancia);
                                            $set('_plan_aplica_ice',         (bool) $sim->aplica_ice);
                                            $set('_plan_ice_categoria',      $sim->ice_categoria);
                                            $set('_plan_ice_porcentaje',     $sim->ice_porcentaje);
                                        })
                                        ->helperText('Al seleccionar una simulación se cargan todos sus parámetros en los campos de abajo. Puedes ajustarlos libremente.'),

                                    Select::make('_plan_presentation_id')
                                        ->label('Presentación')
                                        ->options(function (callable $get) {
                                            $presentations = $get('presentations') ?? [];
                                            $result = [];
                                            $i = 1;
                                            foreach ($presentations as $key => $p) {
                                                $nombre = $p['nombre'] ?? ('Presentación ' . $i++);
                                                $lote   = (float) ($p['cantidad_minima_produccion'] ?? 1);
                                                $result[$key] = $nombre . '  (lote base: ' . rtrim(rtrim(number_format($lote, 4), '0'), '.') . ' u.)';
                                            }
                                            return $result;
                                        })
                                        ->dehydrated(false)
                                        ->live()
                                        ->placeholder('Seleccionar presentación...')
                                        ->columnSpan(1),

                                    TextInput::make('_plan_cantidad')
                                        ->label('Unidades a Producir')
                                        ->numeric()
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->placeholder('Ej: 500')
                                        ->columnSpan(1),

                                    \Filament\Forms\Components\Placeholder::make('_plan_hint')
                                        ->label('Tiempo estimado')
                                        ->columnSpan(1)
                                        ->content(function (callable $get) {
                                            $presKey   = $get('_plan_presentation_id');
                                            $cantidad  = (float) ($get('_plan_cantidad') ?? 0);
                                            $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                            $dias      = max((int) ($get('dias_laborales_mes') ?? 22), 1);
                                            $personas  = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));

                                            if (!$presKey || $cantidad <= 0 || $capacidad <= 0) {
                                                return new \Illuminate\Support\HtmlString('<span class="text-xs text-gray-400">Completa los campos para ver el estimado.</span>');
                                            }

                                            $diaria     = $capacidad / $dias;
                                            $diasNec    = $diaria > 0 ? (int) ceil($cantidad / $diaria) : 0;
                                            $semanasNec = round($diasNec / 5, 1);
                                            $mesesNec   = round($diasNec / $dias, 2);

                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-sm space-y-1">'
                                                . '<div>⏱ <strong>' . $diasNec . ' días hábiles</strong></div>'
                                                . '<div>≈ ' . $semanasNec . ' semanas / ' . $mesesNec . ' mes(es)</div>'
                                                . '<div>👥 ' . (int) $personas . ' persona(s)</div>'
                                                . '</div>'
                                            );
                                        }),

                                ]),

                            // ── Tabla de materiales necesarios ───────────────────
                            \Filament\Forms\Components\Placeholder::make('_plan_materiales')
                                ->label('Materiales Necesarios')
                                ->columnSpanFull()
                                ->content(function (callable $get) {
                                    $presKey  = $get('_plan_presentation_id');
                                    $cantidad = (float) ($get('_plan_cantidad') ?? 0);

                                    if ($presKey === null || $presKey === '' || $cantidad <= 0) {
                                        return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Selecciona una presentación e ingresa la cantidad.</p>');
                                    }

                                    $presentations = $get('presentations') ?? [];
                                    $pres = $presentations[$presKey] ?? null;
                                    if (!$pres || empty($pres['formulaLines'])) {
                                        return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Esta presentación no tiene fórmula definida.</p>');
                                    }

                                    $lote   = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                    $fmt    = fn (float $n): string => rtrim(rtrim(number_format($n, 4), '0'), '.');
                                    $factor = $cantidad / $lote;

                                    $rowsStock   = ''; // insumos con stock suficiente
                                    $rowsComprar = ''; // insumos que hay que comprar
                                    $totalInversion = 0;

                                    foreach ($pres['formulaLines'] as $line) {
                                        $itemId      = $line['inventory_item_id'] ?? null;
                                        $item        = $itemId ? \App\Models\InventoryItem::find($itemId) : null;
                                        $nombre      = $item?->nombre ?? '—';
                                        $unitId      = $line['measurement_unit_id'] ?? null;
                                        $unidadStock  = $item?->measurementUnit?->abreviatura ?? '';
                                        $cantBase     = (float) ($line['cantidad'] ?? 0);
                                        $stockActual  = (float) ($item?->stock_actual ?? 0);

                                        // ── Conversión a unidad de compra ──────────────
                                        $factorConv   = max((float) ($item?->conversion_factor ?? 1), 0.000001);
                                        $puId         = $item?->purchase_unit_id ?? null;
                                        $unidadCompra = $puId ? ($item?->purchaseUnit?->abreviatura ?? $unidadStock) : $unidadStock;
                                        $formulaUnitId = $line['measurement_unit_id'] ?? null;

                                        // cantNecStock en unidades de fórmula
                                        $cantNecFormula = round($cantBase * $factor, 6);
                                        $stockUnitId    = $item?->measurement_unit_id;

                                        // Normalizar cantNecFormula a la unidad de stock del ítem
                                        if ($formulaUnitId == $stockUnitId || !$formulaUnitId) {
                                            // Fórmula ya está en la misma unidad que el stock → directo
                                            $cantNecStock = $cantNecFormula;
                                        } elseif ($puId && $formulaUnitId == $puId && $puId != $stockUnitId) {
                                            // Fórmula en unidad de COMPRA (distinta al stock) → × factor para pasar a stock
                                            $cantNecStock = round($cantNecFormula * $factorConv, 6);
                                        } else {
                                            // Fórmula en unidad menor (ej: ml) y stock en unidad mayor (ej: l) → ÷ factor
                                            $cantNecStock = round($cantNecFormula / $factorConv, 6);
                                        }

                                        // Faltante en unidades de stock
                                        $faltaStock = max($cantNecStock - $stockActual, 0);

                                        if ($puId && $puId != $stockUnitId) {
                                            // Unidades distintas → mostrar en unidad de compra con sub-unidad stock
                                            $cantNecCompra  = round($cantNecStock / $factorConv, 6);
                                            $cantBaseCompra = round($cantBase / $factorConv, 6);
                                            $faltaCompra    = round($faltaStock / $factorConv, 6);
                                            $stockCompra    = round($stockActual / $factorConv, 4);
                                            $cantNecDisplay  = $fmt($cantNecCompra) . ' ' . $unidadCompra
                                                . " <span class='text-gray-400 text-xs'>({$fmt($cantNecStock)} {$unidadStock})</span>";
                                            $cantBaseDisplay = $fmt($cantBaseCompra) . ' ' . $unidadCompra
                                                . " <span class='text-gray-400 text-xs'>({$fmt($cantBase)} {$unidadStock})</span>";
                                            $stockDisplay    = $fmt($stockCompra) . ' ' . $unidadCompra
                                                . " <span class='text-gray-400 text-xs'>({$fmt($stockActual)} {$unidadStock})</span>";
                                            $faltaDisplay    = $fmt($faltaCompra) . ' ' . $unidadCompra
                                                . " <span class='text-gray-400 text-xs'>({$fmt($faltaStock)} {$unidadStock})</span>";
                                        } else {
                                            // Misma unidad de compra y stock → mostrar directamente en unidad de stock
                                            $cantNecDisplay  = $fmt($cantNecStock) . ' ' . $unidadStock;
                                            $cantBaseDisplay = $fmt($cantBase) . ' ' . $unidadStock;
                                            $stockDisplay    = $fmt($stockActual) . ' ' . $unidadStock;
                                            $faltaDisplay    = $fmt($faltaStock) . ' ' . $unidadStock;
                                        }

                                        // Costo solo sobre lo que falta comprar
                                        [$costoPorUnidadStock] = self::costoLinea($item, 1, $unitId);
                                        $costoFalta = round($costoPorUnidadStock * $faltaStock, 4);

                                        if ($faltaStock <= 0) {
                                            // Tiene stock suficiente
                                            $rowsStock .= "<tr class='border-b border-gray-100 dark:border-gray-700 opacity-60'>
                                                <td class='py-1 pr-4 text-sm'>{$nombre}</td>
                                                <td class='py-1 pr-4 text-sm text-right'>{$cantNecDisplay}</td>
                                                <td class='py-1 pr-4 text-sm text-right'>{$stockDisplay}</td>
                                                <td class='py-1 pr-4 text-sm text-right'>—</td>
                                                <td class='py-1 text-sm text-right'>—</td>
                                                <td class='py-1 text-sm'><span style='color:#16a34a'>✓ En stock</span></td>
                                            </tr>";
                                        } else {
                                            // Necesita compra
                                            $totalInversion += $costoFalta;
                                            $rowsComprar .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                                <td class='py-1 pr-4 text-sm font-semibold'>{$nombre}</td>
                                                <td class='py-1 pr-4 text-sm text-right'>{$cantNecDisplay}</td>
                                                <td class='py-1 pr-4 text-sm text-right text-gray-400'>{$stockDisplay}</td>
                                                <td class='py-1 pr-4 text-sm text-right font-semibold' style='color:#ef4444'>{$faltaDisplay}</td>
                                                <td class='py-1 pr-4 text-sm text-right font-mono font-semibold' style='color:#ef4444'>$ " . number_format($costoFalta, 2) . "</td>
                                                <td class='py-1 text-sm'><span class='px-2 py-0.5 rounded text-xs bg-red-100 text-red-700'>Comprar</span></td>
                                            </tr>";
                                        }
                                    }

                                    $thead = "<tr class='border-b border-gray-200 dark:border-gray-600'>
                                        <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Insumo</th>
                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Necesario</th>
                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>En Stock</th>
                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>A Comprar</th>
                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Inversión</th>
                                        <th class='pb-1 text-left text-xs font-semibold text-gray-500 uppercase'>Estado</th>
                                    </tr>";

                                    $filaTotal = "<tr class='border-t-2 border-gray-300 dark:border-gray-500 font-bold'>
                                        <td class='py-2 pr-4 text-sm' colspan='4'>Inversión Requerida en Materiales</td>
                                        <td class='py-2 pr-4 text-sm text-right font-mono' style='color:#dc2626'>$ " . number_format($totalInversion, 2) . "</td>
                                        <td></td>
                                    </tr>";

                                    return new \Illuminate\Support\HtmlString("
                                        <div class='overflow-x-auto'>
                                            <p class='text-xs text-gray-400 mb-2'>Lote base: {$fmt($lote)} u. → Producir: {$fmt($cantidad)} u. (factor: {$fmt($factor)}×)</p>
                                            <table class='w-full'>
                                                <thead>{$thead}</thead>
                                                <tbody>{$rowsComprar}{$rowsStock}{$filaTotal}</tbody>
                                            </table>
                                            <p class='mt-2 text-xs text-gray-400'>* Cantidades en unidad de compra. Entre paréntesis la unidad de stock. La coma (,) es separador de miles.</p>
                                        </div>
                                    ");
                                }),

                            // ── Costos indirectos prorrateados ───────────────────
                            \Filament\Forms\Components\Placeholder::make('_plan_indirectos')
                                ->label('Costos Indirectos Prorrateados')
                                ->columnSpanFull()
                                ->content(function (callable $get) {
                                    $cantidad  = (float) ($get('_plan_cantidad') ?? 0);
                                    $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);

                                    if ($cantidad <= 0 || $capacidad <= 0) {
                                        return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Ingresa la cantidad y la capacidad instalada mensual.</p>');
                                    }

                                    // Usar valores de simulación si están definidos, sino los guardados
                                    $personas = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));
                                    $costoMo  = (float) ($get('_plan_costo_mo_persona') ?: ($get('costo_mano_obra_persona') ?? 0));
                                    $totalMo  = $personas * $costoMo;
                                    $otros    = $get('indirectCosts') ?? [];
                                    $dias     = max((int) ($get('dias_laborales_mes') ?? 22), 1);
                                    $diaria   = $capacidad / $dias;
                                    $diasNec  = $diaria > 0 ? ceil($cantidad / $diaria) : 0;
                                    $fracMes  = $capacidad > 0 ? $cantidad / $capacidad : 0;

                                    $rows            = '';
                                    $totalIndirectos = 0;

                                    // Mano de obra prorrateada
                                    if ($totalMo > 0) {
                                        $prorrateado = round($totalMo * $fracMes, 2);
                                        $totalIndirectos += $prorrateado;
                                        $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                            <td class='py-1 pr-4 text-sm'>Mano de Obra</td>
                                            <td class='py-1 pr-4 text-sm text-gray-400'>{$personas} persona(s) × $ " . number_format($costoMo, 2) . "</td>
                                            <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700'>Mensual</span></td>
                                            <td class='py-1 pr-4 text-sm text-right font-mono'>$ " . number_format($totalMo, 2) . "</td>
                                            <td class='py-1 text-sm text-right font-mono'>$ " . number_format($prorrateado, 2) . "</td>
                                        </tr>";
                                    }

                                    foreach ($otros as $item) {
                                        $monto  = (float) ($item['monto_mensual'] ?? 0);
                                        $frec   = $item['frecuencia'] ?? 'mensual';
                                        $tipo   = match ($item['tipo'] ?? '') {
                                            'diseño_marca' => 'Diseño de Marca',
                                            'publicidad'   => 'Publicidad',
                                            'logistica'    => 'Logística',
                                            'arriendo'     => 'Arriendo',
                                            'servicios'    => 'Servicios',
                                            'otro'         => 'Otro',
                                            default        => '—',
                                        };
                                        $desc   = $item['descripcion'] ?? '';
                                        $nombre = $desc ? "{$tipo} — {$desc}" : $tipo;

                                        // Prorrateo según frecuencia
                                        $prorrateado = match ($frec) {
                                            'semanal' => round(($monto * 4.33) * $fracMes, 2),
                                            'unico'   => round($monto / max($cantidad, 1) * $cantidad, 2), // ÷ unidades producidas
                                            default   => round($monto * $fracMes, 2),
                                        };
                                        $totalIndirectos += $prorrateado;

                                        $badgeColor = match ($frec) {
                                            'semanal' => 'bg-amber-100 text-amber-700',
                                            'unico'   => 'bg-purple-100 text-purple-700',
                                            default   => 'bg-blue-100 text-blue-700',
                                        };
                                        $frecLabel = match ($frec) {
                                            'semanal' => 'Semanal',
                                            'unico'   => 'Un solo pago',
                                            default   => 'Mensual',
                                        };

                                        $notaCol = match ($frec) {
                                            'semanal' => 'Mensualizado: $ ' . number_format($monto * 4.33, 2),
                                            'unico'   => '÷ ' . number_format($cantidad, 0) . ' u. = $ ' . number_format($monto / max($cantidad, 1), 4) . '/u.',
                                            default   => '—',
                                        };

                                        $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                            <td class='py-1 pr-4 text-sm'>{$nombre}</td>
                                            <td class='py-1 pr-4 text-sm text-gray-400'>{$notaCol}</td>
                                            <td class='py-1 pr-4 text-sm text-center'><span class='px-2 py-0.5 rounded text-xs {$badgeColor}'>{$frecLabel}</span></td>
                                            <td class='py-1 pr-4 text-sm text-right font-mono'>$ " . number_format($monto, 2) . "</td>
                                            <td class='py-1 text-sm text-right font-mono'>$ " . number_format($prorrateado, 2) . "</td>
                                        </tr>";
                                    }

                                    if (empty($rows)) {
                                        return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Sin costos indirectos configurados.</p>');
                                    }

                                    $rows .= "<tr class='border-t-2 border-gray-300 dark:border-gray-500 font-bold'>
                                        <td class='py-2 pr-4 text-sm' colspan='4'>Total Indirectos para esta producción</td>
                                        <td class='py-2 text-sm text-right font-mono'>$ " . number_format($totalIndirectos, 2) . "</td>
                                    </tr>";

                                    return new \Illuminate\Support\HtmlString("
                                        <div class='overflow-x-auto mt-1'>
                                            <p class='text-xs text-gray-400 mb-2'>Fracción de capacidad usada: " . number_format($fracMes * 100, 1) . "% del mes ({$diasNec} días hábiles)</p>
                                            <table class='w-full'>
                                                <thead>
                                                    <tr class='border-b border-gray-200 dark:border-gray-600'>
                                                        <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Concepto</th>
                                                        <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Nota</th>
                                                        <th class='pb-1 pr-4 text-center text-xs font-semibold text-gray-500 uppercase'>Frecuencia</th>
                                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Costo Base</th>
                                                        <th class='pb-1 text-right text-xs font-semibold text-gray-500 uppercase'>Prorrateado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>{$rows}</tbody>
                                            </table>
                                            <p class='mt-2 text-xs text-gray-400'>* La coma (,) es separador de miles. Ej: 1,000 = mil.</p>
                                        </div>
                                    ");
                                }),

                            // ── Campos simulación inversión externa ──────────────
                            \Filament\Forms\Components\Grid::make(3)->schema([
                                \Filament\Forms\Components\TextInput::make('_plan_monto_inversion')
                                    ->label('💰 Monto del inversor')
                                    ->numeric()
                                    ->prefix('$')
                                    ->dehydrated(false)
                                    ->live(onBlur: true)
                                    ->placeholder('Ej: 1000')
                                    ->helperText('Capital que aporta el inversor'),

                                \Filament\Forms\Components\TextInput::make('_plan_inversion_interes')
                                    ->label('Tasa de interés acordada')
                                    ->numeric()
                                    ->suffix('%')
                                    ->dehydrated(false)
                                    ->live(onBlur: true)
                                    ->placeholder('Ej: 10')
                                    ->helperText('% que el inversor espera ganar sobre su capital'),

                                \Filament\Forms\Components\TextInput::make('_plan_inversion_meses')
                                    ->label('Plazo de la inversión')
                                    ->numeric()
                                    ->suffix('meses')
                                    ->dehydrated(false)
                                    ->live(onBlur: true)
                                    ->placeholder('Ej: 3')
                                    ->helperText('Tiempo en que se espera devolver capital + interés'),
                            ]),

                            // ── Liquidación financiera completa ──────────────────
                            \Filament\Forms\Components\Placeholder::make('_plan_sensibilidad')
                                ->label('')
                                ->columnSpanFull()
                                ->content(function (callable $get) {
                                    $presKey  = $get('_plan_presentation_id');
                                    $cantidad = (float) ($get('_plan_cantidad') ?? 0);
                                    if (!$presKey || $cantidad <= 0) return new \Illuminate\Support\HtmlString('');
                                    $pres = ($get('presentations') ?? [])[$presKey] ?? null;
                                    if (!$pres) return new \Illuminate\Support\HtmlString('');

                                    [$pres, $lote, $capacidad, $pvpSinIva, $pvpConIva, $margenPct, $icePct, $personas, $costoMo, $indCosts]
                                        = self::escArgs($pres, $get);

                                    $result = self::generarEscenarios($pres, $cantidad, $lote, $capacidad, $pvpSinIva, $pvpConIva, $margenPct, $icePct, $personas, $costoMo, $indCosts);
                                    return new \Illuminate\Support\HtmlString(
                                        self::renderTablaEscenarios($result['escenarios'], $result['peQty'], $cantidad, false)
                                    );
                                }),

                            \Filament\Forms\Components\Placeholder::make('_plan_liquidacion')
                                ->label('Liquidación Financiera')
                                ->columnSpanFull()
                                ->content(function (callable $get) {
                                    $presKey   = $get('_plan_presentation_id');
                                    $cantidad  = (float) ($get('_plan_cantidad') ?? 0);
                                    $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);

                                    if (!$presKey || $cantidad <= 0) {
                                        return new \Illuminate\Support\HtmlString('');
                                    }

                                    $presentations = $get('presentations') ?? [];
                                    $pres = $presentations[$presKey] ?? null;
                                    if (!$pres) return new \Illuminate\Support\HtmlString('');

                                    $lote   = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                    $factor = $cantidad / $lote;

                                    // ── Costos de materiales ─────────────────────────
                                    $totalMatComprar = 0;
                                    $totalMatStock   = 0;
                                    foreach ($pres['formulaLines'] ?? [] as $line) {
                                        $item          = ($line['inventory_item_id'] ?? null) ? \App\Models\InventoryItem::find($line['inventory_item_id']) : null;
                                        $cantBase      = (float) ($line['cantidad'] ?? 0);
                                        $factorConvT   = max((float) ($item?->conversion_factor ?? 1), 0.000001);
                                        $puIdT         = $item?->purchase_unit_id ?? null;
                                        $fUnitId       = $line['measurement_unit_id'] ?? null;
                                        $stockUnitIdT  = $item?->measurement_unit_id;
                                        $cantNecFormula = round($cantBase * $factor, 6);
                                        if ($fUnitId == $stockUnitIdT || !$fUnitId) {
                                            $cantNecStockT = $cantNecFormula;
                                        } elseif ($puIdT && $fUnitId == $puIdT && $puIdT != $stockUnitIdT) {
                                            $cantNecStockT = round($cantNecFormula * $factorConvT, 6);
                                        } else {
                                            $cantNecStockT = round($cantNecFormula / $factorConvT, 6);
                                        }
                                        $stockActual  = (float) ($item?->stock_actual ?? 0);
                                        $faltaStock   = max($cantNecStockT - $stockActual, 0);
                                        $enStock      = min($cantNecStockT, $stockActual);
                                        [$costoPorU]  = self::costoLinea($item, 1, $fUnitId);
                                        $totalMatComprar += $costoPorU * $faltaStock;
                                        $totalMatStock   += $costoPorU * $enStock;
                                    }

                                    // ── Costos indirectos ────────────────────────────
                                    $fracMes     = $capacidad > 0 ? $cantidad / $capacidad : 0;
                                    $personasSim = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));
                                    $costoMoSim  = (float) ($get('_plan_costo_mo_persona') ?: ($get('costo_mano_obra_persona') ?? 0));
                                    $totalMO     = $personasSim * $costoMoSim * $fracMes;
                                    $totalOtrosInd = 0;
                                    foreach ($get('indirectCosts') ?? [] as $indItem) {
                                        $monto = (float) ($indItem['monto_mensual'] ?? 0);
                                        $totalOtrosInd += match ($indItem['frecuencia'] ?? 'mensual') {
                                            'semanal' => $monto * 4.33 * $fracMes,
                                            'unico'   => $monto,
                                            default   => $monto * $fracMes,
                                        };
                                    }
                                    $totalInd       = $totalMO + $totalOtrosInd;

                                    // Costos fijos de empresa prorrateados
                                    $totalFijosEmpresa = self::costosFijosMensuales() * $fracMes;

                                    $costoTotalProd = $totalMatComprar + $totalMatStock + $totalInd + $totalFijosEmpresa;
                                    $inversionReal  = $totalMatComprar + $totalInd + $totalFijosEmpresa;
                                    $costoUnitario  = $cantidad > 0 ? $costoTotalProd / $cantidad : 0;

                                    // ── Tiempo de producción ─────────────────────────
                                    $diasLab = max((int) ($get('dias_laborales_mes') ?? 22), 1);
                                    $diaria  = $capacidad > 0 ? $capacidad / $diasLab : 0;
                                    $diasNec = $diaria > 0 ? (int) ceil($cantidad / $diaria) : 0;

                                    // ── PVP (con/sin IVA según toggle) ───────────────
                                    $incluyeIva = (bool) ($get('_plan_pvp_incluye_iva') ?? false);
                                    $pvpCampo   = (float) ($get('_plan_pvp_venta') ?? 0);
                                    if ($pvpCampo > 0) {
                                        $pvpSinIva = $incluyeIva ? round($pvpCampo / 1.15, 4) : $pvpCampo;
                                    } else {
                                        $margenPct = (float) ($get('_plan_margen_venta') ?? 0);
                                        if ($margenPct <= 0) $margenPct = (float) ($pres['margen_objetivo'] ?? 30);
                                        $divisorMar = 1 - ($margenPct / 100);
                                        $pvpSinIva  = ($divisorMar > 0 && $costoUnitario > 0) ? round($costoUnitario / $divisorMar, 2) : 0;
                                    }
                                    $pvpConIva      = round($pvpSinIva * 1.15, 2);
                                    $margenMostrado = $pvpSinIva > 0 ? round((($pvpSinIva - $costoUnitario) / $pvpSinIva) * 100, 1) : 0;
                                    $ivaRate        = 0.15;
                                    $ingresoNeto    = $pvpSinIva * $cantidad;
                                    $ivaTotal       = round($ingresoNeto * $ivaRate, 2);
                                    $totalFacturado = $ingresoNeto + $ivaTotal;

                                    // ── ICE ─────────────────────────────────────────
                                    $aplicaIce  = (bool) ($get('_plan_aplica_ice') ?? false);
                                    $icePct     = $aplicaIce ? (float) ($get('_plan_ice_porcentaje') ?? 0) / 100 : 0;
                                    $iceTotal   = round($ingresoNeto * $icePct, 2);
                                    $iceCatLabels = [
                                        'cerveza_artesanal' => 'Cerveza artesanal',
                                        'cerveza'           => 'Cerveza industrial',
                                        'vino'              => 'Vino / Fermentados',
                                        'licor_bajo'        => 'Licor < 20° GL',
                                        'licor_medio'       => 'Licor 20–50° GL',
                                        'licor_alto'        => 'Licor > 50° GL',
                                        'otro'              => 'Otro',
                                    ];
                                    $iceCatLabel = $iceCatLabels[$get('_plan_ice_categoria') ?? ''] ?? 'ICE';

                                    // ── Indicadores financieros ──────────────────────
                                    $utilidadBruta   = $ingresoNeto - $costoTotalProd;
                                    $utilidadNeta    = $utilidadBruta - $iceTotal;
                                    $margenBruto     = $ingresoNeto > 0 ? ($utilidadBruta / $ingresoNeto) * 100 : 0;
                                    $margenNeto      = $ingresoNeto > 0 ? ($utilidadNeta / $ingresoNeto) * 100 : 0;
                                    $utilidadPorUnit = $cantidad > 0 ? $utilidadNeta / $cantidad : 0;
                                    // ROI del inversionista: cuánto retorna quien aporta el capital
                                    // Base = inversión real a desembolsar (no cuenta el stock ya en bodega)
                                    $roi             = $inversionReal > 0 ? ($utilidadNeta / $inversionReal) * 100 : 0;

                                    // ── Payback ─────────────────────────────────────
                                    $diasVenta     = (int) ($get('_plan_dias_venta') ?? 0);
                                    $hasDiasVenta  = $diasVenta > 0;
                                    $ingresoDiario = $hasDiasVenta ? $ingresoNeto / $diasVenta : 0;
                                    $paybackDias   = ($ingresoDiario > 0) ? round($inversionReal / $ingresoDiario, 1) : null;

                                    // ── Meta de ganancia ─────────────────────────────
                                    $metaGanancia = max((float) ($get('_plan_meta_ganancia') ?? 5), 0.1);
                                    $roiOk = $roi >= $metaGanancia;
                                    $extrasPercent = max(0, $metaGanancia - 5);
                                    $diasExtra = round(($extrasPercent / 2) * 15);

                                    // ── Helpers de estilo ────────────────────────────
                                    $cGreen = '#16a34a'; $cRed = '#dc2626'; $cBlue = '#2563eb';
                                    $cPurp  = '#7c3aed'; $cAmb  = '#d97706'; $cGray = '#6b7280';
                                    $cUtil  = $utilidadNeta >= 0 ? $cGreen : $cRed;
                                    $cUtilBruta = $utilidadBruta >= 0 ? $cGreen : $cRed;
                                    $fmt    = fn (float $n): string => number_format($n, 2);
                                    $pct    = fn (float $n): string => number_format($n, 1) . '%';

                                    // ── C1: Badge simulación ─────────────────────────
                                    $simBadge = ($get('_plan_num_personas') || $get('_plan_costo_mo_persona'))
                                        ? '<div style="margin-bottom:1rem;padding:0.6rem 1rem;border-radius:0.5rem;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:0.75rem;font-weight:600;">⚠ Modo simulación — Mano de Obra con valores ajustados manualmente</div>'
                                        : '';

                                    // Fila de tabla
                                    $tr = fn (string $lbl, string $val, bool $bold = false, string $color = '', bool $sub = false): string =>
                                        '<tr><td style="padding:0.35rem 0.75rem 0.35rem ' . ($sub ? '1.5rem' : '0.25rem') . ';font-size:0.8rem;'
                                        . ($bold ? 'font-weight:700;border-top:1px solid #e5e7eb;' : '') . 'color:#374151;">' . $lbl . '</td>'
                                        . '<td style="padding:0.35rem 0;font-size:0.8rem;text-align:right;white-space:nowrap;'
                                        . ($bold ? 'font-weight:700;border-top:1px solid #e5e7eb;' : '') . ($color ? "color:{$color};" : '') . '">' . $val . '</td></tr>';

                                    // Encabezado de sección
                                    $sec = fn (string $emoji, string $title, string $color): string =>
                                        '<div style="display:flex;align-items:center;gap:0.4rem;padding-bottom:0.4rem;margin-bottom:0.5rem;border-bottom:2px solid ' . $color . '30;">'
                                        . '<span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:' . $color . ';">' . $emoji . ' ' . $title . '</span></div>';

                                    // KPI grande (5 tarjetas en 1 fila)
                                    $kpiBig = function (string $title, string $valTotal, string $valUnit, string $color, bool $last = false, string $extra = '') use ($cGray): string {
                                        return '<div style="padding:1rem 1.25rem;' . (!$last ? 'border-right:1px solid #e5e7eb;' : '') . '">'
                                            . '<p style="font-size:0.6rem;text-transform:uppercase;font-weight:600;color:' . $cGray . ';margin-bottom:0.35rem;">' . $title . '</p>'
                                            . '<p style="font-size:1.35rem;font-weight:800;color:' . $color . ';line-height:1.1;margin-bottom:0.2rem;">' . $valTotal . '</p>'
                                            . '<p style="font-size:0.7rem;color:' . $cGray . ';margin-bottom:0.1rem;">' . $valUnit . '</p>'
                                            . ($extra ? '<p style="font-size:0.65rem;color:' . $cGray . ';line-height:1.4;margin-top:0.3rem;">' . $extra . '</p>' : '')
                                            . '</div>';
                                    };

                                    // KPI pequeño (5 tarjetas operación)
                                    $kpi = fn (string $title, string $val, string $sub, string $color, bool $last = false): string =>
                                        '<div style="padding:1rem 1.25rem;' . (!$last ? 'border-right:1px solid #e5e7eb;' : '') . '">'
                                        . '<p style="font-size:0.65rem;text-transform:uppercase;font-weight:600;color:' . $cGray . ';margin-bottom:0.25rem;">' . $title . '</p>'
                                        . '<p style="font-size:1.15rem;font-weight:700;color:' . $color . ';line-height:1.2;">' . $val . '</p>'
                                        . '<p style="font-size:0.7rem;color:' . $cGray . ';margin-top:0.15rem;">' . $sub . '</p>'
                                        . '</div>';

                                    // ── HTML ─────────────────────────────────────────
                                    $html = $simBadge;

                                    if ($pvpSinIva <= 0) {
                                        $html .= '<div style="padding:1rem;border-radius:0.75rem;background:#fef3c7;border:1px solid #fde68a;color:#92400e;font-size:0.85rem;">'
                                            . '⚠ Ingresa el <strong>Margen de Utilidad (%)</strong> en la sección "Precio de Venta e Impuestos" para ver la liquidación.'
                                            . '</div>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    }

                                    // ── C2: Header resumen ───────────────────────────
                                    $presNombre = $pres['nombre'] ?? 'Presentación';
                                    $html .= '<div style="margin-bottom:1rem;padding:0.75rem 1rem;border-radius:0.5rem;background:#f8fafc;border:1px solid #e2e8f0;">'
                                        . '<span style="font-weight:700;color:#1e293b;">' . $presNombre . '</span>'
                                        . ' &nbsp;·&nbsp; <span style="color:' . $cGray . ';font-size:0.85rem;">' . number_format($cantidad, 0) . ' unidades</span>'
                                        . ' &nbsp;·&nbsp; <span style="font-weight:600;color:' . $cGreen . ';">PVP $ ' . $fmt($pvpSinIva) . ' sin IVA &nbsp;/&nbsp; $ ' . $fmt($pvpConIva) . ' con IVA</span>'
                                        . ' &nbsp;·&nbsp; <span style="color:' . $cBlue . ';font-size:0.8rem;">Margen ' . number_format($margenMostrado, 1) . '%</span>'
                                        . ($aplicaIce ? ' &nbsp;·&nbsp; <span style="background:#fef3c7;color:#92400e;padding:0.1rem 0.5rem;border-radius:0.25rem;font-size:0.75rem;font-weight:600;">ICE ' . $iceCatLabel . ' ' . number_format($icePct * 100, 0) . '%</span>' : '')
                                        . '</div>';

                                    // ── C3: KPI cards GRANDES (5 en 1 fila) ──────────
                                    $totalImpuestos = $ivaTotal + $iceTotal;
                                    $roiExplain = 'Por cada $1 invertido recibes $' . number_format(1 + $roi / 100, 2) . ' de vuelta. Equivale a ' . number_format($roi, 1) . 'x más rentable que un depósito bancario al 1%.';
                                    $html .= '<div style="margin-bottom:1.25rem;border-radius:0.75rem;border:1px solid #e5e7eb;overflow:hidden;">'
                                        . '<div style="background:#f8fafc;padding:0.5rem 1rem;border-bottom:1px solid #e5e7eb;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:' . $cBlue . ';">📊 Indicadores Financieros Clave</div>'
                                        . '<div style="display:grid;grid-template-columns:repeat(5,1fr);">';
                                    $html .= $kpiBig('Margen Bruto', $pct($margenBruto), $pct($margenNeto) . ' neto', $cBlue);
                                    $html .= $kpiBig('Utilidad Bruta', '$ ' . $fmt($utilidadBruta), '$ ' . number_format($cantidad > 0 ? $utilidadBruta / $cantidad : 0, 4) . ' / u.', $cUtilBruta);
                                    $html .= $kpiBig('Impuestos (IVA+ICE)', '$ ' . $fmt($totalImpuestos), '$ ' . number_format($cantidad > 0 ? $totalImpuestos / $cantidad : 0, 4) . ' / u.', $cAmb);
                                    $html .= $kpiBig('Rentabilidad ROI', $pct($roi), 'Retorno sobre costo total', $cPurp, false, $roiExplain);
                                    $html .= $kpiBig('Utilidad Neta', '$ ' . $fmt($utilidadNeta), '$ ' . number_format($utilidadPorUnit, 4) . ' / u.', $cUtil, true);
                                    $html .= '</div></div>';

                                    // ── C4: Tablas de detalle ────────────────────────
                                    $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.25rem;">';

                                    // Columna izquierda
                                    $html .= '<div>';
                                    $html .= '<div style="margin-bottom:1.25rem;background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                        . $sec('💰', 'Ingresos', $cGreen)
                                        . '<table style="width:100%;">'
                                        . $tr('Ingresos netos (' . number_format($cantidad, 0) . ' u. × $ ' . $fmt($pvpSinIva) . ')', '$ ' . $fmt($ingresoNeto), true, $cGreen)
                                        . $tr('IVA cobrado al cliente (15%)  ↗', '$ ' . $fmt($ivaTotal), false, $cGray, true)
                                        . $tr('Total facturado al cliente', '$ ' . $fmt($totalFacturado))
                                        . '</table></div>';
                                    $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                        . $sec('🏭', 'Costos de Producción', $cRed)
                                        . '<table style="width:100%;">'
                                        . $tr('Materiales a comprar', '$ ' . $fmt($totalMatComprar))
                                        . $tr('Materiales en stock (disponible)', '$ ' . $fmt($totalMatStock), false, $cGray, true)
                                        . $tr('Mano de Obra', '$ ' . $fmt($totalMO), false, '', true)
                                        . $tr('Otros costos indirectos', '$ ' . $fmt($totalOtrosInd), false, '', true)
                                        . $tr('Costos fijos empresa (prorrateo)', '$ ' . $fmt($totalFijosEmpresa), false, '#7c3aed', true)
                                        . $tr('Costo total de producción', '$ ' . $fmt($costoTotalProd), true, $cRed)
                                        . $tr('Costo por unidad', '$ ' . number_format($costoUnitario, 4), false, $cBlue)
                                        . '</table></div>';
                                    $html .= '</div>';

                                    // Columna derecha
                                    $html .= '<div>';
                                    $html .= '<div style="margin-bottom:1.25rem;background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                        . $sec('🧾', 'Impuestos', $cAmb)
                                        . '<table style="width:100%;">'
                                        . $tr('IVA 15% — passthrough (lo cobras y remites al SRI)', '$ ' . $fmt($ivaTotal), false, $cGray);
                                    if ($aplicaIce) {
                                        $html .= $tr('ICE ' . $iceCatLabel . ' (' . number_format($icePct * 100, 0) . '% sobre precio s/IVA)', '$ ' . $fmt($iceTotal), false, $cAmb)
                                            . $tr('↳ Costo real para el productor', '', false, $cGray, true);
                                    } else {
                                        $html .= $tr('ICE', 'No aplica', false, $cGray);
                                    }
                                    $html .= $tr('Total impuestos del productor', '$ ' . $fmt($iceTotal), true, $cAmb)
                                        . '</table></div>';
                                    $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                        . $sec('📊', 'Resultado', $cBlue)
                                        . '<table style="width:100%;">'
                                        . $tr('Utilidad Bruta', '$ ' . $fmt($utilidadBruta), false, $cUtilBruta)
                                        . $tr('Margen Bruto', $pct($margenBruto), false, $cUtilBruta, true)
                                        . ($aplicaIce ? $tr('– ICE (' . number_format($icePct * 100, 0) . '%)', '– $ ' . $fmt($iceTotal), false, $cAmb, true) : '')
                                        . $tr('Utilidad Neta (después de ICE)', '$ ' . $fmt($utilidadNeta), true, $cUtil)
                                        . $tr('Margen Neto', $pct($margenNeto), false, $cUtil, true)
                                        . $tr('Utilidad por unidad', '$ ' . number_format($utilidadPorUnit, 4), false, $cUtil)
                                        . $tr('ROI (sobre costo total)', $pct($roi), false, $cPurp)
                                        . '</table></div>';
                                    $html .= '</div>';

                                    $html .= '</div>'; // grid C4

                                    // ── C5: KPIs de operación ────────────────────────
                                    $html .= '<div style="margin-bottom:1.25rem;border-radius:0.75rem;border:1px solid #e5e7eb;overflow:hidden;">'
                                        . '<div style="background:#f8fafc;padding:0.6rem 1rem;border-bottom:1px solid #e5e7eb;">'
                                        . '<span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:' . $cBlue . ';">⏱ Recuperación de Inversión &amp; Operación</span></div>'
                                        . '<div style="display:grid;grid-template-columns:repeat(5,1fr);">';
                                    $html .= $kpi('Inversión a Desembolsar', '$ ' . $fmt($inversionReal), 'Compras + M.O. + indirectos', $cRed);
                                    $html .= $kpi('Costo Total', '$ ' . $fmt($costoTotalProd), 'Incluye stock disponible', $cGray);
                                    $html .= $kpi('Tiempo Producción', $diasNec > 0 ? "{$diasNec} días háb." : '—', $capacidad > 0 ? '≈ ' . round($diasNec / 5, 1) . ' semanas' : 'Configura capacidad', $cBlue);
                                    if ($hasDiasVenta) {
                                        $html .= $kpi('Período de Venta', "{$diasVenta} días", 'Estimado para vender todo', $cGray);
                                        if ($paybackDias !== null) {
                                            $ok = $paybackDias <= $diasVenta;
                                            $html .= $kpi('Payback', "{$paybackDias} días", $ok ? '✓ Dentro del período de venta' : '⚠ Supera el período de venta', $ok ? $cGreen : $cAmb, true);
                                        } else {
                                            $html .= $kpi('Payback', '—', 'Ingresa el PVP de venta', $cGray, true);
                                        }
                                    } else {
                                        $html .= $kpi('Período de Venta', '—', 'Ingresa en "Precio e Impuestos"', $cGray);
                                        $html .= $kpi('Payback', '—', 'Requiere días de venta', $cGray, true);
                                    }
                                    $html .= '</div></div>';

                                    // ── C9: Simulación de inversión externa ──────────
                                    $montoInv = (float) ($get('_plan_monto_inversion') ?? 0);
                                    $tasaInteres = (float) ($get('_plan_inversion_interes') ?? 0);
                                    $mesesInv    = (float) ($get('_plan_inversion_meses') ?? 0);

                                    if ($montoInv > 0 && $inversionReal > 0 && $ingresoNeto > 0) {
                                        // Fracción de la producción que financia el inversor
                                        $fracInv    = min($montoInv / $inversionReal, 1.0);
                                        $unidsInv   = round($cantidad * $fracInv, 0);

                                        // Interés acordado (interés simple sobre el plazo pactado)
                                        $hasAcuerdo      = $tasaInteres > 0 && $mesesInv > 0;
                                        $interesAcordado = $hasAcuerdo ? round($montoInv * ($tasaInteres / 100) * $mesesInv, 2) : 0;
                                        $totalAPagar     = round($montoInv + $interesAcordado, 2);

                                        // Utilidad real que genera la producción para esa fracción
                                        $utilInv     = round($utilidadNeta * $fracInv, 2);
                                        $excedente   = round($utilInv - $interesAcordado, 2); // positivo = te sobra, negativo = no alcanza
                                        $roiEfectivo = $montoInv > 0 ? round(($utilInv / $montoInv) * 100, 1) : 0;
                                        $cInv        = $roiEfectivo >= $tasaInteres ? '#4ade80' : '#fca5a5';
                                        $alcanza     = $utilInv >= $interesAcordado;

                                        // Hito: al qué % de ventas recupera el inversor su capital + interés acordado
                                        $unidsParaCapital    = $pvpSinIva > 0 ? $montoInv / $pvpSinIva : 0;
                                        $unidsParaTotalPagar = $pvpSinIva > 0 ? $totalAPagar / $pvpSinIva : 0;
                                        $pctCapital          = $cantidad > 0 ? min(round($unidsParaCapital / $cantidad * 100, 1), 100) : 0;
                                        $pctTotalPagar       = $cantidad > 0 ? min(round($unidsParaTotalPagar / $cantidad * 100, 1), 100) : 0;
                                        $pctGananciaCompleta = round($fracInv * 100, 1);

                                        $html .= '<div style="margin-bottom:1.25rem;padding:1.5rem;border-radius:0.75rem;background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);border:2px solid #3b82f6;color:#fff;">';
                                        $html .= '<p style="font-weight:700;font-size:1rem;margin-bottom:0.25rem;color:#93c5fd;">🤝 Simulación de Inversión Externa</p>';

                                        // Subtítulo con parámetros del acuerdo
                                        if ($hasAcuerdo) {
                                            $html .= '<p style="font-size:0.75rem;color:#94a3b8;margin-bottom:1.25rem;">'
                                                . 'Capital <strong style="color:#fbbf24;">$ ' . $fmt($montoInv) . '</strong>'
                                                . ' · Interés acordado <strong style="color:#fbbf24;">' . $tasaInteres . '% mensual</strong>'
                                                . ' · Plazo <strong style="color:#fbbf24;">' . $mesesInv . ' meses</strong>'
                                                . ' → Total a devolver <strong style="color:#a78bfa;">$ ' . $fmt($totalAPagar) . '</strong>'
                                                . ' <span style="font-size:0.68rem;color:#64748b;">(interés simple: capital × tasa × meses)</span>'
                                                . '</p>';
                                        } else {
                                            $html .= '<p style="font-size:0.75rem;color:#94a3b8;margin-bottom:1.25rem;">'
                                                . 'Capital <strong style="color:#fbbf24;">$ ' . $fmt($montoInv) . '</strong>'
                                                . ' <span style="font-size:0.68rem;color:#64748b;">— ingresa tasa e interés para simular el acuerdo con el inversor</span>'
                                                . '</p>';
                                        }

                                        // KPIs
                                        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.25rem;">';
                                        $kpiCards = [
                                            ['Financia', number_format($fracInv * 100, 1) . '%', 'de la producción', '#fbbf24'],
                                            ['Unidades', number_format($unidsInv, 0), 'de ' . number_format($cantidad, 0) . ' totales', '#93c5fd'],
                                            ['Utilidad generada', '$ ' . $fmt($utilInv), 'lo que produce su lote', '#4ade80'],
                                        ];
                                        if ($hasAcuerdo) {
                                            $kpiCards[] = ['Interés acordado', '$ ' . $fmt($interesAcordado), $tasaInteres . '% × ' . $mesesInv . ' meses', '#fbbf24'];
                                            $kpiCards[] = ['Total a devolver', '$ ' . $fmt($totalAPagar), 'capital + interés', '#a78bfa'];
                                            $kpiCards[] = $alcanza
                                                ? ['Excedente', '$ ' . $fmt($excedente), 'te sobra después de pagar', '#4ade80']
                                                : ['Déficit', '$ ' . $fmt(abs($excedente)), 'no cubre el interés acordado', '#fca5a5'];
                                        } else {
                                            $kpiCards[] = ['ROI efectivo', $roiEfectivo . '%', 'retorno real de la producción', $cInv];
                                            $kpiCards[] = ['Total retorno', '$ ' . $fmt($montoInv + $utilInv), 'capital + utilidad real', '#a78bfa'];
                                        }
                                        foreach ($kpiCards as $k) {
                                            $html .= '<div style="background:rgba(255,255,255,0.07);border-radius:0.6rem;padding:0.9rem 1rem;text-align:center;">'
                                                . '<p style="font-size:0.65rem;color:#94a3b8;margin-bottom:0.2rem;text-transform:uppercase;letter-spacing:.04em;">' . $k[0] . '</p>'
                                                . '<p style="font-size:1.4rem;font-weight:800;color:' . $k[3] . ';line-height:1.1;">' . $k[1] . '</p>'
                                                . '<p style="font-size:0.65rem;color:#cbd5e1;">' . $k[2] . '</p>'
                                                . '</div>';
                                        }
                                        $html .= '</div>';

                                        // ── Hitos por % de ventas ──
                                        $html .= '<div style="background:rgba(255,255,255,0.05);border-radius:0.6rem;padding:1rem 1.25rem;margin-bottom:1rem;">';
                                        $html .= '<p style="font-size:0.78rem;font-weight:700;color:#e2e8f0;margin-bottom:0.75rem;">📊 Hitos de recuperación por % de ventas</p>';

                                        // Barra visual
                                        $html .= '<div style="position:relative;height:10px;background:rgba(255,255,255,0.1);border-radius:9999px;margin-bottom:0.75rem;">';
                                        $html .= '<div style="position:absolute;left:0;top:0;height:10px;width:' . min($pctCapital,100) . '%;background:#fbbf24;border-radius:9999px;"></div>';
                                        if ($hasAcuerdo) {
                                            $html .= '<div style="position:absolute;left:0;top:0;height:10px;width:' . min($pctTotalPagar,100) . '%;background:#a78bfa;border-radius:9999px;opacity:0.6;"></div>';
                                        }
                                        $html .= '<div style="position:absolute;left:0;top:0;height:10px;width:' . min($pctGananciaCompleta,100) . '%;background:#4ade80;border-radius:9999px;opacity:0.35;"></div>';
                                        $html .= '</div>';

                                        $html .= '<div style="display:flex;flex-wrap:wrap;gap:1.5rem;">';
                                        $html .= '<div>'
                                            . '<p style="font-size:0.68rem;color:#94a3b8;text-transform:uppercase;">🟡 Recupera capital</p>'
                                            . '<p style="font-size:1.05rem;font-weight:700;color:#fbbf24;">al ' . $pctCapital . '% de ventas</p>'
                                            . '<p style="font-size:0.68rem;color:#64748b;">≈ ' . number_format(ceil($unidsParaCapital), 0) . ' unidades</p>'
                                            . '</div>';
                                        if ($hasAcuerdo) {
                                            $html .= '<div>'
                                                . '<p style="font-size:0.68rem;color:#94a3b8;text-transform:uppercase;">🟣 Capital + interés acordado</p>'
                                                . '<p style="font-size:1.05rem;font-weight:700;color:#a78bfa;">al ' . $pctTotalPagar . '% de ventas</p>'
                                                . '<p style="font-size:0.68rem;color:#64748b;">≈ ' . number_format(ceil($unidsParaTotalPagar), 0) . ' unidades · $ ' . $fmt($totalAPagar) . '</p>'
                                                . '</div>';
                                        }
                                        $html .= '<div>'
                                            . '<p style="font-size:0.68rem;color:#94a3b8;text-transform:uppercase;">🟢 Toda la utilidad de su lote</p>'
                                            . '<p style="font-size:1.05rem;font-weight:700;color:#4ade80;">al ' . $pctGananciaCompleta . '% de ventas</p>'
                                            . '<p style="font-size:0.68rem;color:#64748b;">≈ ' . number_format($unidsInv, 0) . ' unidades · $ ' . $fmt($montoInv + $utilInv) . '</p>'
                                            . '</div>';
                                        $html .= '</div></div>';

                                        // Párrafo explicativo
                                        $html .= '<p style="font-size:0.75rem;color:#cbd5e1;line-height:1.8;">';
                                        if ($hasAcuerdo) {
                                            $html .= 'El inversor aporta <strong style="color:#fbbf24;">$ ' . $fmt($montoInv) . '</strong> financiando el <strong style="color:#fbbf24;">' . number_format($fracInv * 100, 1) . '%</strong> de la producción. '
                                                . 'Con una tasa del <strong style="color:#fbbf24;">' . $tasaInteres . '% mensual</strong> a <strong style="color:#fbbf24;">' . $mesesInv . ' meses</strong>, '
                                                . 'el interés acordado es <strong style="color:#a78bfa;">$ ' . $fmt($interesAcordado) . '</strong> — total a devolver <strong style="color:#a78bfa;">$ ' . $fmt($totalAPagar) . '</strong>. ';
                                            if ($alcanza) {
                                                $html .= 'La producción genera <strong style="color:#4ade80;">$ ' . $fmt($utilInv) . '</strong> de utilidad para esa fracción, '
                                                    . '<strong style="color:#4ade80;">cubriendo el acuerdo y dejando un excedente de $ ' . $fmt($excedente) . '</strong>. '
                                                    . 'Pagas al inversor al ' . $pctTotalPagar . '% de ventas y el resto es tuyo.';
                                            } else {
                                                $html .= '<strong style="color:#fca5a5;">⚠ La utilidad generada ($ ' . $fmt($utilInv) . ') no alcanza a cubrir el interés acordado.</strong> '
                                                    . 'Hay un déficit de <strong style="color:#fca5a5;">$ ' . $fmt(abs($excedente)) . '</strong>. '
                                                    . 'Considera subir el PVP, reducir costos o renegociar la tasa.';
                                            }
                                        } else {
                                            $html .= 'Con <strong style="color:#fbbf24;">$ ' . $fmt($montoInv) . '</strong> el inversor financia el <strong style="color:#fbbf24;">' . number_format($fracInv * 100, 1) . '%</strong> de la producción. '
                                                . 'Recupera el capital al ' . $pctCapital . '% de ventas y obtiene toda su utilidad (<strong style="color:#4ade80;">$ ' . $fmt($utilInv) . '</strong>) al ' . $pctGananciaCompleta . '% de ventas. '
                                                . 'Ingresa tasa y plazo para simular el acuerdo financiero.';
                                        }
                                        $html .= '</p>';

                                        $html .= '</div>';
                                    }

                                    // ══════════════════════════════════════════════════
                                    // LIQUIDACIÓN — CANAL DISTRIBUIDORES
                                    // ══════════════════════════════════════════════════
                                    $pvpDistribuidor  = (float) ($get('precio_distribuidor') ?? 0);
                                    $cantMinDist      = (int)   ($get('cantidad_minima_distribuidor') ?? 10);
                                    $margenDistPct    = (float) ($get('margen_distribuidor') ?? 40);

                                    $html .= '<div style="margin-top:2rem;border-top:3px solid #6366f1;padding-top:1.5rem;">';
                                    $html .= '<div style="margin-bottom:1rem;padding:0.6rem 1rem;background:#f5f3ff;border-radius:0.5rem;border:1px solid #c4b5fd;">'
                                        . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#4c1d95;">🚚 LIQUIDACIÓN — Canal Distribuidores</span>'
                                        . '<span style="float:right;font-size:0.7rem;color:#6d28d9;">Pedido mínimo: ' . $cantMinDist . ' u. para aplicar precio distribuidor</span>'
                                        . '</div>';

                                    // ── Precio de distribuidor con desglose IVA ───────
                                    if ($pvpDistribuidor > 0) {
                                        $pvpDistConIva = round($pvpDistribuidor * 1.15, 4);
                                        $html .= '<div style="margin-bottom:1rem;display:flex;align-items:center;gap:1.5rem;padding:0.6rem 1rem;background:#fff;border:1px solid #e0e7ff;border-radius:0.5rem;flex-wrap:wrap;">';
                                        $html .= '<div>'
                                            . '<span style="font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;color:#6b7280;">Precio Distribuidor</span><br>'
                                            . '<strong style="font-size:1rem;color:#4c1d95;">$ ' . number_format($pvpDistribuidor, 2) . '</strong>';

                                        // Si el PVP directo incluye IVA, aclarar que el dist. es sin IVA
                                        if ($incluyeIva) {
                                            $html .= ' <span style="font-size:0.7rem;color:#6b7280;">(sin IVA)</span>';
                                        }
                                        $html .= '</div>';

                                        $html .= '<div style="border-left:1px solid #e0e7ff;padding-left:1.5rem;">'
                                            . '<span style="font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;color:#6b7280;">+ IVA 15% → precio al distribuidor</span><br>'
                                            . '<strong style="font-size:0.9rem;color:#374151;">$ ' . number_format($pvpDistConIva, 2) . '</strong>'
                                            . '</div>';

                                        $html .= '<div style="border-left:1px solid #e0e7ff;padding-left:1.5rem;">'
                                            . '<span style="font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;color:#6b7280;">Margen sobre PVP directo</span><br>'
                                            . '<strong style="font-size:0.9rem;color:#6d28d9;">' . number_format($margenDistPct, 1) . '%</strong>'
                                            . '</div>';

                                        if ($pvpSinIva > 0) {
                                            $difPrecio = $pvpSinIva - $pvpDistribuidor;
                                            $html .= '<div style="border-left:1px solid #e0e7ff;padding-left:1.5rem;">'
                                                . '<span style="font-size:0.65rem;text-transform:uppercase;letter-spacing:0.06em;color:#6b7280;">Diferencia vs PVP directo</span><br>'
                                                . '<strong style="font-size:0.9rem;color:#dc2626;">– $ ' . number_format($difPrecio, 2) . ' / u.</strong>'
                                                . '</div>';
                                        }

                                        $html .= '</div>';
                                    }

                                    if ($pvpDistribuidor <= 0) {
                                        $html .= '<div style="padding:1.5rem;border:2px dashed #c4b5fd;border-radius:0.75rem;text-align:center;color:#7c3aed;">'
                                            . '<p style="font-size:0.85rem;margin-bottom:0.4rem;">📐 Configura el precio de distribuidor</p>'
                                            . '<p style="font-size:0.75rem;color:#a78bfa;">Ve a la sección <strong>Precio de Venta e Impuestos</strong> e ingresa el precio distribuidor o el margen.</p>'
                                            . '</div>';
                                    } else {
                                        // ── Cálculos distribuidores ──────────────────────
                                        $ingresoNetoDist    = $pvpDistribuidor * $cantidad;
                                        $ivaDistribuidor    = $pvpDistribuidor * 0.15 * $cantidad;
                                        $totalFacturadoDist = $ingresoNetoDist + $ivaDistribuidor;
                                        $iceTotalDist       = $aplicaIce ? round($pvpDistribuidor * $icePct * $cantidad, 2) : 0;
                                        $utilidadBrutaDist  = $ingresoNetoDist - $costoTotalProd;
                                        $margenBrutoDist    = $ingresoNetoDist > 0 ? ($utilidadBrutaDist / $ingresoNetoDist) * 100 : 0;
                                        $utilidadNetaDist   = $utilidadBrutaDist - $iceTotalDist;
                                        $margenNetoDist     = $ingresoNetoDist > 0 ? ($utilidadNetaDist / $ingresoNetoDist) * 100 : 0;
                                        $roiDist            = $costoTotalProd > 0 ? ($utilidadNetaDist / $costoTotalProd) * 100 : 0;
                                        $utilPorUnitDist    = $cantidad > 0 ? $utilidadNetaDist / $cantidad : 0;
                                        $totalImpDist       = $ivaDistribuidor + $iceTotalDist;

                                        // Payback distribuidores
                                        $paybackDiasDist = null;
                                        if ($hasDiasVenta && $diasVenta > 0 && $ingresoNetoDist > 0 && $inversionReal > 0) {
                                            $ingresosDiarioDist = $ingresoNetoDist / $diasVenta;
                                            $paybackDiasDist    = (int) ceil($inversionReal / $ingresosDiarioDist);
                                        }
                                        $unidadesPaybackDist = $pvpDistribuidor > 0 ? (int) ceil($inversionReal / $pvpDistribuidor) : 0;

                                        // Diferencia vs venta directa
                                        $difUtil  = $utilidadNetaDist - $utilidadNeta;
                                        $difSign  = $difUtil >= 0 ? '+' : '';
                                        $difColor = $difUtil >= 0 ? $cGreen : $cRed;

                                        $cPurpD = '#7c3aed';
                                        $cUtilD = $utilidadNetaDist >= 0 ? $cGreen : $cRed;
                                        $cUtilBrutaD = $utilidadBrutaDist >= 0 ? '#16a34a' : $cRed;

                                        // ── D3: KPI cards GRANDES ─────────────────────────
                                        $roiExplainDist = 'Por cada $1 invertido recibes $' . number_format(1 + $roiDist / 100, 2) . ' de vuelta en el canal distribuidores.';
                                        $html .= '<div style="margin-bottom:1.25rem;border-radius:0.75rem;border:1px solid #e5e7eb;overflow:hidden;">'
                                            . '<div style="background:#f5f3ff;padding:0.5rem 1rem;border-bottom:1px solid #e5e7eb;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:' . $cPurpD . ';">📊 Indicadores Financieros — Distribuidores</div>'
                                            . '<div style="display:grid;grid-template-columns:repeat(5,1fr);">';
                                        $html .= $kpiBig('Margen Bruto', $pct($margenBrutoDist), $pct($margenNetoDist) . ' neto', $cPurpD);
                                        $html .= $kpiBig('Utilidad Bruta', '$ ' . $fmt($utilidadBrutaDist), '$ ' . number_format($cantidad > 0 ? $utilidadBrutaDist / $cantidad : 0, 4) . ' / u.', $cUtilBrutaD);
                                        $html .= $kpiBig('Impuestos (IVA+ICE)', '$ ' . $fmt($totalImpDist), '$ ' . number_format($cantidad > 0 ? $totalImpDist / $cantidad : 0, 4) . ' / u.', $cAmb);
                                        $html .= $kpiBig('Rentabilidad ROI', $pct($roiDist), 'Retorno sobre costo total', $cPurpD, false, $roiExplainDist);
                                        $html .= $kpiBig('Utilidad Neta', '$ ' . $fmt($utilidadNetaDist), '$ ' . number_format($utilPorUnitDist, 4) . ' / u.', $cUtilD, true);
                                        $html .= '</div></div>';

                                        // ── D4: Tablas de detalle ─────────────────────────
                                        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.25rem;">';

                                        // Columna izquierda
                                        $html .= '<div>';
                                        $html .= '<div style="margin-bottom:1.25rem;background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                            . $sec('💰', 'Ingresos (Distribuidor)', $cPurpD)
                                            . '<table style="width:100%;">'
                                            . $tr('Ingresos netos (' . number_format($cantidad, 0) . ' u. × $ ' . $fmt($pvpDistribuidor) . ')', '$ ' . $fmt($ingresoNetoDist), true, $cPurpD)
                                            . $tr('IVA cobrado al distribuidor (15%)  ↗', '$ ' . $fmt($ivaDistribuidor), false, $cGray, true)
                                            . $tr('Total facturado al distribuidor', '$ ' . $fmt($totalFacturadoDist))
                                            . '</table></div>';
                                        $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                            . $sec('🏭', 'Costos de Producción', $cRed)
                                            . '<table style="width:100%;">'
                                            . $tr('Materiales a comprar', '$ ' . $fmt($totalMatComprar))
                                            . $tr('Materiales en stock (disponible)', '$ ' . $fmt($totalMatStock), false, $cGray, true)
                                            . $tr('Mano de Obra', '$ ' . $fmt($totalMO), false, '', true)
                                            . $tr('Otros costos indirectos', '$ ' . $fmt($totalOtrosInd), false, '', true)
                                            . $tr('Costos fijos empresa (prorrateo)', '$ ' . $fmt($totalFijosEmpresa), false, '#7c3aed', true)
                                            . $tr('Costo total de producción', '$ ' . $fmt($costoTotalProd), true, $cRed)
                                            . $tr('Costo por unidad', '$ ' . number_format($costoUnitario, 4), false, $cBlue)
                                            . '</table></div>';
                                        $html .= '</div>';

                                        // Columna derecha
                                        $html .= '<div>';
                                        $html .= '<div style="margin-bottom:1.25rem;background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                            . $sec('🧾', 'Impuestos', $cAmb)
                                            . '<table style="width:100%;">'
                                            . $tr('IVA 15% — passthrough', '$ ' . $fmt($ivaDistribuidor), false, $cGray);
                                        if ($aplicaIce) {
                                            $html .= $tr('ICE ' . $iceCatLabel . ' (' . number_format($icePct * 100, 0) . '% sobre precio dist.)', '$ ' . $fmt($iceTotalDist), false, $cAmb)
                                                . $tr('↳ Costo real para el productor', '', false, $cGray, true);
                                        } else {
                                            $html .= $tr('ICE', 'No aplica', false, $cGray);
                                        }
                                        $html .= $tr('Total impuestos del productor', '$ ' . $fmt($iceTotalDist), true, $cAmb)
                                            . '</table></div>';
                                        $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">'
                                            . $sec('📊', 'Resultado', $cPurpD)
                                            . '<table style="width:100%;">'
                                            . $tr('Utilidad Bruta', '$ ' . $fmt($utilidadBrutaDist), false, $cUtilBrutaD)
                                            . $tr('Margen Bruto', $pct($margenBrutoDist), false, $cUtilBrutaD, true)
                                            . ($aplicaIce ? $tr('– ICE (' . number_format($icePct * 100, 0) . '%)', '– $ ' . $fmt($iceTotalDist), false, $cAmb, true) : '')
                                            . $tr('Utilidad Neta (después de ICE)', '$ ' . $fmt($utilidadNetaDist), true, $cUtilD)
                                            . $tr('Margen Neto', $pct($margenNetoDist), false, $cUtilD, true)
                                            . $tr('Utilidad por unidad', '$ ' . number_format($utilPorUnitDist, 4), false, $cUtilD)
                                            . $tr('ROI (sobre costo total)', $pct($roiDist), false, $cPurpD)
                                            . $tr('vs. Venta Directa', $difSign . '$ ' . $fmt($difUtil), false, $difColor, true)
                                            . '</table></div>';
                                        $html .= '</div>';

                                        $html .= '</div>'; // grid D4

                                        // ── D5: KPIs de operación ─────────────────────────
                                        $html .= '<div style="margin-bottom:1.25rem;border-radius:0.75rem;border:1px solid #e5e7eb;overflow:hidden;">'
                                            . '<div style="background:#f5f3ff;padding:0.6rem 1rem;border-bottom:1px solid #e5e7eb;">'
                                            . '<span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:' . $cPurpD . ';">⏱ Recuperación — Canal Distribuidores</span></div>'
                                            . '<div style="display:grid;grid-template-columns:repeat(5,1fr);">';
                                        $html .= $kpi('Inversión a Desembolsar', '$ ' . $fmt($inversionReal), 'Igual que venta directa', $cRed);
                                        $html .= $kpi('Precio Distribuidor', '$ ' . $fmt($pvpDistribuidor), 'Margen ' . $margenDistPct . '% sobre PVP', $cPurpD);
                                        $html .= $kpi('Pedido Mínimo', $cantMinDist . ' u.', 'Para aplicar precio distribuidor', $cBlue);
                                        $html .= $kpi('Unidades para Payback', $unidadesPaybackDist > 0 ? number_format($unidadesPaybackDist) . ' u.' : '—', 'Para recuperar inversión', $cAmb);
                                        if ($hasDiasVenta) {
                                            if ($paybackDiasDist !== null) {
                                                $okDist = $paybackDiasDist <= $diasVenta;
                                                $html .= $kpi('Payback', "{$paybackDiasDist} días", $okDist ? '✓ Dentro del período de venta' : '⚠ Supera el período de venta', $okDist ? $cGreen : $cAmb, true);
                                            } else {
                                                $html .= $kpi('Payback', '—', 'Ingresa PVP de venta', $cGray, true);
                                            }
                                        } else {
                                            $html .= $kpi('Payback', '—', 'Requiere días de venta', $cGray, true);
                                        }
                                        $html .= '</div></div>';

                                        // ── D6: Párrafo estratégico ──────────────────────
                                        $pvpDistPar     = '$' . number_format($pvpDistribuidor, 2);
                                        $pvpDistIvaPar  = '$' . number_format($pvpDistribuidor * 1.15, 2);
                                        $ingDistPar     = '$' . number_format($ingresoNetoDist, 2);
                                        $utilDistPar    = '$' . number_format($utilidadNetaDist, 2);
                                        $utilUnitDistPar = '$' . number_format($utilPorUnitDist, 4);
                                        $roiDistPar     = number_format($roiDist, 1) . '%';
                                        $margenDistNPar = number_format($margenNetoDist, 1) . '%';
                                        $difUtilPar     = ($difUtil >= 0 ? '+' : '') . '$' . number_format($difUtil, 2);
                                        $paybackDistStr = $paybackDiasDist !== null ? $paybackDiasDist . ' días' : 'N/D';

                                        $comparacion = $difUtil >= 0
                                            ? "Aunque el precio unitario es menor que el canal directo, la certeza de ventas en volumen ({$cantMinDist}+ u. por pedido) puede compensar con menor riesgo de inventario sin vender."
                                            : "El canal distribuidor genera <strong style=\"color:{$cRed};\">{$difUtilPar}</strong> menos de utilidad que la venta directa. Evalúa si el beneficio en volumen y menor costo de venta justifica este sacrificio de margen.";

                                        $parrafoDist = "Si destinas toda tu producción de <strong>" . number_format($cantidad, 0) . " unidades</strong> al canal distribuidor, "
                                            . "las vendes a <strong>{$pvpDistPar} / u.</strong> (sin IVA; {$pvpDistIvaPar} con IVA al distribuidor), "
                                            . "generando un ingreso total de <strong>{$ingDistPar}</strong>. "
                                            . "Tu <strong>utilidad neta será {$utilDistPar}</strong> — <strong>{$utilUnitDistPar} / unidad</strong>, "
                                            . "con margen del <strong>{$margenDistNPar}</strong> y ROI del <strong>{$roiDistPar}</strong>. "
                                            . "Necesitas vender al menos <strong>" . number_format($unidadesPaybackDist) . " unidades</strong> para recuperar tu inversión"
                                            . ($paybackDiasDist !== null ? " (~{$paybackDistStr})" : "") . ". "
                                            . $comparacion;

                                        $distBadgeColor  = $utilidadNetaDist >= 0 ? '#6d28d9' : '#dc2626';
                                        $distBadgeBg     = $utilidadNetaDist >= 0 ? '#f5f3ff' : '#fef2f2';
                                        $distBadgeBorder = $utilidadNetaDist >= 0 ? '#c4b5fd' : '#fecaca';
                                        $distLabel       = $utilidadNetaDist >= 0 ? '✓ RENTABLE EN DISTRIBUCIÓN' : '✗ NO RENTABLE EN DISTRIBUCIÓN';

                                        $html .= '<div style="margin-bottom:1.25rem;padding:1.25rem 1.5rem;border-radius:0.75rem;background:#f5f3ff;border:1px solid #c4b5fd;">'
                                            . '<p style="font-weight:700;color:#4c1d95;font-size:0.9rem;margin-bottom:0.75rem;">🚚 Análisis Estratégico — Canal Distribuidores</p>'
                                            . '<p style="font-size:0.82rem;color:#374151;line-height:1.7;">' . $parrafoDist . '</p>'
                                            . '<hr style="border:none;border-top:1px solid #c4b5fd;margin:0.75rem 0;">'
                                            . '<div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">'
                                            . '<span style="font-size:0.75rem;font-weight:700;color:#4c1d95;">🎯 Precio Distribuidor: ' . $pvpDistPar . ' | Margen: ' . $margenDistPct . '%</span>'
                                            . '<span style="padding:0.2rem 0.75rem;border-radius:999px;font-size:0.7rem;font-weight:700;background:' . $distBadgeBg . ';border:1px solid ' . $distBadgeBorder . ';color:' . $distBadgeColor . ';">' . $distLabel . '</span>'
                                            . '<span style="font-size:0.7rem;color:#6d28d9;">Diferencia vs venta directa: <strong style="color:' . $difColor . ';">' . $difSign . '$ ' . $fmt(abs($difUtil)) . '</strong></span>'
                                            . '</div>'
                                            . '</div>';
                                    }

                                    $html .= '</div>'; // cierre bloque distribuidores

                                    // ══════════════════════════════════════════════════
                                    // ANÁLISIS ESTRATÉGICO Y RECOMENDACIONES
                                    // ══════════════════════════════════════════════════
                                    $html .= '<div style="margin-top:2.5rem;border-top:3px solid #3b82f6;padding-top:1.5rem;">';
                                    $html .= '<div style="margin-bottom:1rem;padding:0.6rem 1rem;background:#eff6ff;border-radius:0.5rem;border:1px solid #bfdbfe;">'
                                        . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#1e3a5f;">📋 ANÁLISIS ESTRATÉGICO Y RECOMENDACIONES</span>'
                                        . '</div>';

                                    // ── C6: Párrafo estratégico ──────────────────────
                                    $presNombrePar = $pres['nombre'] ?? 'este producto';
                                    $unidadesPar   = number_format($cantidad, 0);
                                    $invPar        = '$' . number_format($inversionReal, 2);
                                    $pvpPar        = '$' . number_format($pvpSinIva, 2);
                                    $pvpIvaPar     = '$' . number_format($pvpSinIva * 1.15, 2);
                                    $ingPar        = '$' . number_format($ingresoNeto, 2);
                                    $utilPar       = '$' . number_format($utilidadNeta, 2);
                                    $utilUnitPar   = '$' . number_format($utilidadPorUnit, 4);
                                    $roiPar        = number_format($roi, 1) . '%';
                                    $margenPar     = number_format($margenNeto, 1) . '%';
                                    $paybackStr    = ($paybackDias !== null) ? $paybackDias . ' días' : 'N/D (ingresa días de venta)';
                                    $diasVentaStr  = $hasDiasVenta ? $diasVenta . ' días' : 'no definido';
                                    $metaFrase = $roiOk
                                        ? "cumpliendo tu meta de rentabilidad del {$metaGanancia}% con margen de maniobra"
                                        : "sin alcanzar aún tu meta del {$metaGanancia}% — necesitarás ajustar precio o reducir costos";
                                    $sensibilidadFrase = $diasExtra > 0
                                        ? "Nota: tu meta del {$metaGanancia}% está {$extrasPercent}% por encima del umbral base del 5%, lo que implica ~{$diasExtra} días adicionales de tiempo de recuperación estimado versus un escenario base."
                                        : "Tu meta está en el umbral base del 5%, óptima para un inicio de operaciones.";
                                    $unidadesPayback = ($pvpSinIva > 0) ? (int) ceil($inversionReal / $pvpSinIva) : 0;

                                    $parrafo = "Estás planificando producir <strong>{$unidadesPar} unidades</strong> de <strong>{$presNombrePar}</strong>, "
                                        . "lo que requiere una <strong>inversión de {$invPar}</strong> en materiales, mano de obra y gastos operativos. "
                                        . "Al precio de venta de <strong>{$pvpPar} por unidad</strong> (sin IVA; {$pvpIvaPar} al consumidor final con IVA del 15%), "
                                        . "generarás un ingreso total de <strong>{$ingPar}</strong>. "
                                        . "Después de cubrir todos los costos de producción e impuestos aplicables, "
                                        . "tu <strong>ganancia neta será de {$utilPar}</strong> — es decir, <strong>{$utilUnitPar} por cada unidad vendida</strong>, "
                                        . "con un margen neto del <strong>{$margenPar}</strong> y una rentabilidad (ROI) del <strong>{$roiPar}</strong>. "
                                        . "Para recuperar la inversión solo necesitas vender <strong>{$unidadesPayback} unidades</strong>"
                                        . ($paybackDias !== null ? ", lo que a tu ritmo de ventas toma aproximadamente <strong>{$paybackStr}</strong> (de {$diasVentaStr} totales proyectados)" : "")
                                        . ", " . $metaFrase . ". " . $sensibilidadFrase;

                                    $metaBadgeColor = $roiOk ? '#16a34a' : '#dc2626';
                                    $metaBadgeBg    = $roiOk ? '#f0fdf4' : '#fef2f2';
                                    $metaBadgeBorder = $roiOk ? '#bbf7d0' : '#fecaca';
                                    $metaLabel = $roiOk ? '✓ META CUMPLIDA' : '✗ META NO ALCANZADA';

                                    $html .= '<div style="margin-bottom:1.25rem;padding:1.25rem 1.5rem;border-radius:0.75rem;background:#eff6ff;border:1px solid #bfdbfe;">'
                                        . '<p style="font-weight:700;color:#1e3a5f;font-size:0.9rem;margin-bottom:0.75rem;">📋 Análisis Estratégico de la Producción</p>'
                                        . '<p style="font-size:0.82rem;color:#374151;line-height:1.7;">' . $parrafo . '</p>'
                                        . '<hr style="border:none;border-top:1px solid #bfdbfe;margin:0.75rem 0;">'
                                        . '<div style="display:flex;align-items:center;gap:0.75rem;">'
                                        . '<span style="font-size:0.75rem;font-weight:700;color:#1e3a5f;">🎯 Meta de Rentabilidad: ' . number_format($metaGanancia, 1) . '%</span>'
                                        . '<span style="padding:0.2rem 0.75rem;border-radius:999px;font-size:0.7rem;font-weight:700;background:' . $metaBadgeBg . ';border:1px solid ' . $metaBadgeBorder . ';color:' . $metaBadgeColor . ';">' . $metaLabel . '</span>'
                                        . '</div>'
                                        . '</div>';

                                    // ── C7: Recomendaciones estratégicas ─────────────
                                    $pctMat = $costoTotalProd > 0 ? ($totalMatComprar + $totalMatStock) / $costoTotalProd * 100 : 0;
                                    $pctInd = $costoTotalProd > 0 ? $totalInd / $costoTotalProd * 100 : 0;
                                    $capacidad2 = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                    $usoCap = $capacidad2 > 0 ? ($cantidad / $capacidad2) * 100 : 0;
                                    $recomendaciones = [];

                                    if ($usoCap < 40 && $capacidad2 > 0) {
                                        $cantOptima = round($capacidad2 * 0.6);
                                        $recomendaciones[] = [
                                            'tipo'  => '⚡ Capacidad Sub-utilizada',
                                            'color' => '#f59e0b',
                                            'texto' => 'Solo usas el ' . number_format($usoCap, 1) . '% de tu capacidad instalada mensual. '
                                                . 'Producir ' . number_format($cantOptima, 0) . ' unidades (60% de capacidad) '
                                                . 'reduciría tu costo unitario de indirectos de $' . number_format($costoUnitario, 4) . ' '
                                                . 'a ~$' . number_format(($totalMatComprar + $totalMatStock) / $cantOptima + $totalInd * 0.6 / $cantOptima, 4) . ' '
                                                . 'y mejoraría tu margen. <strong>Considera aumentar el volumen de producción.</strong>',
                                        ];
                                    }

                                    if ($pctMat > 65) {
                                        $recomendaciones[] = [
                                            'tipo'  => '🔩 Materiales Dominan el Costo',
                                            'color' => '#dc2626',
                                            'texto' => 'Los materiales representan el ' . number_format($pctMat, 1) . '% de tu costo total. '
                                                . 'Con este nivel de dependencia en insumos, cualquier alza de precios de proveedores afecta directamente tu margen. '
                                                . '<strong>Negocia contratos de volumen o busca proveedores alternativos para reducir este porcentaje por debajo del 60%.</strong>',
                                        ];
                                    }

                                    if ($pctInd > 45) {
                                        $recomendaciones[] = [
                                            'tipo'  => '💡 Costos Indirectos Elevados',
                                            'color' => '#7c3aed',
                                            'texto' => 'Los costos indirectos (mano de obra + overhead) representan el ' . number_format($pctInd, 1) . '% de tu costo. '
                                                . 'Producir en mayor volumen amortizaría mejor estos costos fijos. '
                                                . '<strong>Analiza si puedes consolidar turnos o reducir personal ocioso en tiempos de baja producción.</strong>',
                                        ];
                                    }

                                    if ($margenNeto < 20 && $pvpSinIva > 0) {
                                        $pvpRecomendado = $costoUnitario > 0 ? round($costoUnitario / (1 - 0.25), 2) : 0;
                                        $recomendaciones[] = [
                                            'tipo'  => '📉 Margen Neto Ajustado',
                                            'color' => '#dc2626',
                                            'texto' => 'Con un margen neto del ' . number_format($margenNeto, 1) . '% tienes poco colchón ante imprevistos. '
                                                . 'El umbral saludable para un producto manufacturado es 25-35%. '
                                                . '<strong>Considera ajustar el PVP a $' . $pvpRecomendado . ' para alcanzar un margen del 25%</strong>, '
                                                . 'o reducir costos de materiales o indirectos.',
                                        ];
                                    }

                                    if ($paybackDias !== null && $hasDiasVenta && $paybackDias > $diasVenta) {
                                        $recomendaciones[] = [
                                            'tipo'  => '⏱ Recuperación Más Lenta que la Venta',
                                            'color' => '#f59e0b',
                                            'texto' => "Tu payback ({$paybackDias} días) supera tu período de venta proyectado ({$diasVenta} días). "
                                                . 'Esto significa que terminarías de vender antes de recuperar la inversión — '
                                                . 'necesitarás capital de trabajo adicional para el siguiente ciclo. '
                                                . '<strong>Aumenta el precio de venta o reduce la inversión inicial comprando menos materiales por ronda.</strong>',
                                        ];
                                    }

                                    if ($roi >= 50 && $margenNeto >= 25) {
                                        $recomendaciones[] = [
                                            'tipo'  => '🏆 Posición Competitiva Fuerte',
                                            'color' => '#16a34a',
                                            'texto' => 'Con un ROI del ' . number_format($roi, 1) . '% y margen neto del ' . number_format($margenNeto, 1) . '%, '
                                                . 'este producto tiene una posición competitiva sólida (estrategia de diferenciación según Porter). '
                                                . '<strong>Prioriza el crecimiento de volumen y la fidelización de clientes para consolidar ventaja de mercado.</strong>',
                                        ];
                                    }

                                    if (empty($recomendaciones)) {
                                        $recomendaciones[] = [
                                            'tipo'  => '✅ Estructura Equilibrada',
                                            'color' => '#16a34a',
                                            'texto' => 'Tu estructura de costos está bien balanceada. Mantén el monitoreo mensual de los indicadores y ajusta el precio según la inflación de insumos.',
                                        ];
                                    }

                                    $html .= '<div style="margin-bottom:1.25rem;padding:1.25rem 1.5rem;border-radius:0.75rem;background:#f9fafb;border:1px solid #e5e7eb;">'
                                        . '<p style="font-weight:700;color:#1e293b;font-size:0.9rem;margin-bottom:0.25rem;">💡 Recomendaciones Estratégicas</p>'
                                        . '<p style="font-size:0.72rem;color:' . $cGray . ';margin-bottom:1rem;">(basadas en tu estructura de costos actual)</p>';
                                    foreach ($recomendaciones as $rec) {
                                        $html .= '<div style="margin-bottom:0.75rem;padding:0.75rem 1rem;background:#fff;border-radius:0.5rem;border-left:4px solid ' . $rec['color'] . ';border:1px solid #e5e7eb;border-left:4px solid ' . $rec['color'] . ';">'
                                            . '<p style="font-size:0.78rem;font-weight:700;color:' . $rec['color'] . ';margin-bottom:0.3rem;">' . $rec['tipo'] . '</p>'
                                            . '<p style="font-size:0.78rem;color:#374151;line-height:1.6;">' . $rec['texto'] . '</p>'
                                            . '</div>';
                                    }
                                    $html .= '</div>';

                                    $html .= '</div>'; // cierre bloque análisis estratégico

                                    // ── C8: Nota al pie ──────────────────────────────
                                    $html .= '<p style="margin-top:0.75rem;font-size:0.68rem;color:' . $cGray . ';line-height:1.6;">'
                                        . '* <strong>IVA</strong>: passthrough — lo cobras al cliente y lo remites al SRI, no impacta tu utilidad. '
                                        . '* <strong>Payback</strong>: días necesarios para recuperar la inversión con los ingresos de ventas. '
                                        . ($aplicaIce ? '* <strong>ICE</strong>: calculado sobre el precio ex-fábrica (PVP sin IVA). Verifique tarifas vigentes con el SRI. ' : '')
                                        . '* Los materiales en stock se consideran en el costo total pero no en la inversión a desembolsar.'
                                        . '</p>';

                                    return new \Illuminate\Support\HtmlString($html);
                                }),

                            // ── Acciones de liquidación ───────────────────────────
                            \Filament\Forms\Components\Actions::make([

                                // ── Comparar canales ──────────────────────────────────
                                \Filament\Forms\Components\Actions\Action::make('comparar_canales')
                                    ->label('⚖️ Comparar Canales')
                                    ->color('info')
                                    ->icon('heroicon-o-scale')
                                    ->modalHeading('Comparativa: Venta Directa vs Distribuidores')
                                    ->modalWidth('5xl')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Cerrar')
                                    ->mountUsing(function (\Filament\Forms\Form $form, callable $get) {
                                        // ── Leer estado del formulario ────────────────
                                        $presKey  = $get('_plan_presentation_id');
                                        $cantidad = (float) ($get('_plan_cantidad') ?? 0);
                                        $pres     = ($get('presentations') ?? [])[$presKey] ?? null;

                                        if (!$presKey || $cantidad <= 0 || !$pres) {
                                            $form->fill(['_comp_html' => '<p style="color:#6b7280;padding:2rem;text-align:center;">Configura la producción en la pestaña <strong>Planificación</strong> para ver la comparativa.</p>']);
                                            return;
                                        }

                                        $pvpCampo        = (float) ($get('_plan_pvp_venta') ?? 0);
                                        $incluyeIva      = (bool)  ($get('_plan_pvp_incluye_iva') ?? false);
                                        $pvpSinIva       = ($pvpCampo > 0 && $incluyeIva) ? round($pvpCampo / 1.15, 4) : $pvpCampo;
                                        $pvpDistribuidor = (float) ($get('precio_distribuidor') ?? 0);
                                        $cantMinDist     = (int)   ($get('cantidad_minima_distribuidor') ?? 10);
                                        $margenDistPct   = (float) ($get('margen_distribuidor') ?? 40);
                                        $margenPct       = (float) ($get('_plan_margen_venta') ?? 0);
                                        $capacidad       = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                        $lote            = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                        $factor          = $cantidad / $lote;

                                        // Materiales (separado en stock y comprar)
                                        $totalMatStock   = 0;
                                        $totalMatComprar = 0;
                                        foreach ($pres['formulaLines'] ?? [] as $line) {
                                            $item        = ($line['inventory_item_id'] ?? null) ? \App\Models\InventoryItem::find($line['inventory_item_id']) : null;
                                            $cantBase    = (float) ($line['cantidad'] ?? 0);
                                            $factorConvT = max((float) ($item?->conversion_factor ?? 1), 0.000001);
                                            $puIdT       = $item?->purchase_unit_id ?? null;
                                            $fUnitId     = $line['measurement_unit_id'] ?? null;
                                            $stockUnitIdT = $item?->measurement_unit_id;
                                            $cantNecFormula = round($cantBase * $factor, 6);
                                            if ($fUnitId == $stockUnitIdT || !$fUnitId) $cantNecStock = $cantNecFormula;
                                            elseif ($puIdT && $fUnitId == $puIdT && $puIdT != $stockUnitIdT) $cantNecStock = round($cantNecFormula * $factorConvT, 6);
                                            else $cantNecStock = round($cantNecFormula / $factorConvT, 6);
                                            [$costoPorU] = \App\Filament\App\Resources\ProductDesignResource::costoLinea($item, 1, $fUnitId);
                                            $enStock = min($cantNecStock, max(0, (float) ($item?->stock_actual ?? 0)));
                                            $totalMatStock   += $costoPorU * $enStock;
                                            $totalMatComprar += $costoPorU * max(0, $cantNecStock - $enStock);
                                        }
                                        $totalMat = $totalMatStock + $totalMatComprar;

                                        // Indirectos (separado MO y otros)
                                        $fracMes  = $capacidad > 0 ? $cantidad / $capacidad : 0;
                                        $personas = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));
                                        $costoMo  = (float) ($get('_plan_costo_mo_persona') ?: ($get('costo_mano_obra_persona') ?? 0));
                                        $totalMO  = $personas * $costoMo * $fracMes;
                                        $totalOtrosInd = 0;
                                        foreach ($get('indirectCosts') ?? [] as $ind) {
                                            $m = (float) ($ind['monto_mensual'] ?? 0);
                                            $totalOtrosInd += match ($ind['frecuencia'] ?? 'mensual') {
                                                'semanal' => $m * 4.33 * $fracMes,
                                                'unico'   => $m,
                                                default   => $m * $fracMes,
                                            };
                                        }
                                        $totalInd = $totalMO + $totalOtrosInd;

                                        // Costos fijos de empresa prorrateados
                                        $totalFijosEmpresa = self::costosFijosMensuales() * $fracMes;

                                        $costoTotal    = $totalMat + $totalInd + $totalFijosEmpresa;
                                        $costoUnitario = $cantidad > 0 ? $costoTotal / $cantidad : 0;
                                        $inversionReal = $totalMatComprar + $totalInd + $totalFijosEmpresa;

                                        if ($pvpSinIva <= 0 && $margenPct > 0) {
                                            $div = 1 - $margenPct / 100;
                                            $pvpSinIva = $div > 0 ? round($costoUnitario / $div, 2) : 0;
                                        }

                                        $icePct      = (bool) ($get('_plan_aplica_ice') ?? false)
                                            ? (float) ($get('_plan_ice_porcentaje') ?? 0) / 100 : 0;
                                        $iceCatLabel = $get('_plan_ice_categoria') ?? '';

                                        // ── Venta Directa ──────────────────────────
                                        $pvpConIva     = $incluyeIva ? $pvpCampo : round($pvpSinIva * 1.15, 4);
                                        $ingresoBrutoD = $pvpConIva * $cantidad;
                                        $ingresoD      = $pvpSinIva * $cantidad;   // ingreso neto (sin IVA)
                                        $ivaUnitD      = round($pvpSinIva * 0.15, 4);
                                        $ivaTotalD     = round($ivaUnitD * $cantidad, 2);
                                        $iceD          = round($ingresoD * $icePct, 2);
                                        $utilBrutaD    = $ingresoD - $costoTotal;
                                        $utilNetaD     = $utilBrutaD - $iceD;
                                        $margenBrutaD  = $ingresoD > 0 ? ($utilBrutaD / $ingresoD) * 100 : 0;
                                        $margenND      = $ingresoD > 0 ? ($utilNetaD / $ingresoD) * 100 : 0;
                                        $roiD          = $costoTotal > 0 ? ($utilNetaD / $costoTotal) * 100 : 0;
                                        $utilUnitD     = $cantidad > 0 ? $utilNetaD / $cantidad : 0;

                                        // ── Canal Distribuidores ────────────────────
                                        $pvpDistConIva     = round($pvpDistribuidor * 1.15, 4);
                                        $ingresoDistTiene  = $pvpDistribuidor > 0;
                                        $ingresoBrutoDist  = $pvpDistConIva * $cantidad;
                                        $ingresoDist       = $pvpDistribuidor * $cantidad;  // ingreso neto (sin IVA)
                                        $ivaUnitDist       = round($pvpDistribuidor * 0.15, 4);
                                        $ivaTotalDist      = round($ivaUnitDist * $cantidad, 2);
                                        $iceDist           = round($ingresoDist * $icePct, 2);
                                        $utilBrutaDist     = $ingresoDist - $costoTotal;
                                        $utilNetaDist      = $utilBrutaDist - $iceDist;
                                        $margenBrutaDist   = $ingresoDist > 0 ? ($utilBrutaDist / $ingresoDist) * 100 : 0;
                                        $margenNDist       = $ingresoDist > 0 ? ($utilNetaDist / $ingresoDist) * 100 : 0;
                                        $roiDist           = $costoTotal > 0 ? ($utilNetaDist / $costoTotal) * 100 : 0;
                                        $utilUnitDist      = $cantidad > 0 ? $utilNetaDist / $cantidad : 0;

                                        $difUtil  = $utilNetaDist - $utilNetaD;
                                        $difSign  = $difUtil >= 0 ? '+' : '';
                                        $diasVenta = (int) ($get('_plan_dias_venta') ?? 0);

                                        // ── HTML helpers ───────────────────────────
                                        $fmt = fn ($v) => number_format((float) $v, 2);
                                        $pct = fn ($v) => number_format((float) $v, 1) . '%';

                                        $badge = function (bool $ok, string $label) {
                                            $bg  = $ok ? '#f0fdf4' : '#fef2f2';
                                            $cl  = $ok ? '#16a34a' : '#dc2626';
                                            $bd  = $ok ? '#bbf7d0' : '#fecaca';
                                            return '<span style="display:inline-block;padding:0.2rem 0.6rem;border-radius:999px;font-size:0.7rem;font-weight:700;background:' . $bg . ';border:1px solid ' . $bd . ';color:' . $cl . ';">' . $label . '</span>';
                                        };

                                        $secHead = fn (string $ico, string $title, string $bg, string $cl) =>
                                            '<div style="padding:0.4rem 0.75rem;background:' . $bg . ';border-radius:0.4rem;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:' . $cl . ';margin-bottom:0.5rem;">' . $ico . ' ' . $title . '</div>';

                                        $tr2 = function (string $label, string $v, bool $bold = false, string $color = '', bool $indent = false) {
                                            $bS = $bold ? 'font-weight:700;' : '';
                                            $cS = $color ? "color:{$color};" : '';
                                            $iS = $indent ? 'padding-left:1.5rem;color:#6b7280;font-size:0.75rem;' : '';
                                            return '<tr><td style="padding:0.3rem 0.5rem;font-size:0.78rem;' . $iS . '">' . $label . '</td>'
                                                . '<td style="padding:0.3rem 0.5rem;font-size:0.78rem;text-align:right;' . $bS . $cS . '">' . $v . '</td></tr>';
                                        };

                                        $presNombre = $pres['nombre'] ?? 'Presentación';
                                        $ganador    = $utilNetaD >= $utilNetaDist ? 'Venta Directa' : 'Canal Distribuidor';
                                        $ganadorClr = $utilNetaD >= $utilNetaDist ? '#16a34a' : '#6d28d9';

                                        $html = '<div style="font-family:sans-serif;font-size:13px;">';

                                        // ── Header ─────────────────────────────────
                                        $html .= '<div style="padding:1rem 1.5rem;background:linear-gradient(135deg,#1e3a5f,#1e40af);border-radius:0.75rem;margin-bottom:1.25rem;">'
                                            . '<h2 style="margin:0;color:#fff;font-size:1rem;">⚖️ Comparativa de Canales de Venta</h2>'
                                            . '<p style="margin:0.2rem 0 0;color:#bfdbfe;font-size:0.78rem;"><strong>' . e($presNombre) . '</strong> · ' . number_format($cantidad, 0) . ' u. · Costo total: $ ' . $fmt($costoTotal) . ' · Inversión: $ ' . $fmt($inversionReal) . '</p>'
                                            . ($incluyeIva ? '<p style="margin:0.15rem 0 0;font-size:0.7rem;color:#93c5fd;">PVP ingresado con IVA incluido — base de cálculo: $ ' . $fmt($pvpSinIva) . ' / u.</p>' : '')
                                            . '</div>';

                                        // ── Desglose de Costos de Producción ───────
                                        $html .= $secHead('⚙️', 'Desglose de Costos de Producción', '#f8fafc', '#374151');
                                        $html .= '<table style="width:100%;border:1px solid #e5e7eb;border-radius:0.4rem;margin-bottom:1.25rem;">';
                                        $html .= $tr2('Materias primas en stock (disponible)', '$ ' . $fmt($totalMatStock), false, '#6b7280', true);
                                        $html .= $tr2('Materias primas a comprar', '$ ' . $fmt($totalMatComprar), false, '#dc2626', true);
                                        $html .= $tr2('Total materias primas', '$ ' . $fmt($totalMat), true);
                                        $html .= $tr2('Mano de obra', '$ ' . $fmt($totalMO), false, '', true);
                                        $html .= $tr2('Otros costos indirectos', '$ ' . $fmt($totalOtrosInd), false, '', true);
                                        $html .= $tr2('Costos fijos empresa (prorrateo)', '$ ' . $fmt($totalFijosEmpresa), false, '#7c3aed', true);
                                        $html .= $tr2('Total costo de producción', '$ ' . $fmt($costoTotal), true, '#dc2626');
                                        $html .= $tr2('Inversión a desembolsar', '$ ' . $fmt($inversionReal), false, '#7c3aed');
                                        $html .= $tr2('Costo por unidad', '$ ' . $fmt($costoUnitario), false, '#1e40af');
                                        $html .= '</table>';

                                        // ── IVA Recaudado ───────────────────────────
                                        $html .= $secHead('🧾', 'IVA Recaudado — Total por Producción', '#fefce8', '#92400e');
                                        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">';

                                        // Directa
                                        $html .= '<div style="padding:0.75rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;">';
                                        $html .= '<p style="margin:0 0 0.4rem;font-size:0.72rem;font-weight:700;color:#1e40af;">🏪 Venta Directa</p>';
                                        $html .= '<table style="width:100%;">';
                                        $html .= $tr2('Precio base / u. (sin IVA)', '$ ' . $fmt($pvpSinIva));
                                        if ($incluyeIva) {
                                            $html .= $tr2('Precio al público / u. (con IVA)', '$ ' . $fmt($pvpConIva), false, '#1e40af');
                                        }
                                        $html .= $tr2('IVA 15% por unidad', '$ ' . $fmt($ivaUnitD), false, '#d97706');
                                        $html .= $tr2('IVA total (' . number_format($cantidad, 0) . ' u.)', '$ ' . $fmt($ivaTotalD), true, '#d97706');
                                        $html .= $tr2('↳ Remitir al SRI (passthrough)', '$ ' . $fmt($ivaTotalD), false, '#6b7280', true);
                                        $html .= '</table></div>';

                                        // Distribuidores
                                        $html .= '<div style="padding:0.75rem;background:' . ($ingresoDistTiene ? '#f5f3ff' : '#f9fafb') . ';border:1px solid ' . ($ingresoDistTiene ? '#c4b5fd' : '#e5e7eb') . ';border-radius:0.5rem;">';
                                        $html .= '<p style="margin:0 0 0.4rem;font-size:0.72rem;font-weight:700;color:#4c1d95;">🚚 Canal Distribuidores</p>';
                                        if ($ingresoDistTiene) {
                                            $html .= '<table style="width:100%;">';
                                            $html .= $tr2('Precio distribuidor / u. (sin IVA)', '$ ' . $fmt($pvpDistribuidor));
                                            $html .= $tr2('Precio al distribuidor / u. (con IVA)', '$ ' . $fmt(round($pvpDistribuidor * 1.15, 2)), false, '#4c1d95');
                                            $html .= $tr2('IVA 15% por unidad', '$ ' . $fmt($ivaUnitDist), false, '#d97706');
                                            $html .= $tr2('IVA total (' . number_format($cantidad, 0) . ' u.)', '$ ' . $fmt($ivaTotalDist), true, '#d97706');
                                            $html .= $tr2('↳ Remitir al SRI (passthrough)', '$ ' . $fmt($ivaTotalDist), false, '#6b7280', true);
                                            $html .= '</table>';
                                        } else {
                                            $html .= '<p style="font-size:0.75rem;color:#9ca3af;margin:0;">Precio de distribuidor no configurado.</p>';
                                        }
                                        $html .= '</div></div>';

                                        // ── Liquidación detallada lado a lado ──────
                                        $html .= $secHead('📊', 'Liquidación Detallada por Canal', '#f8fafc', '#374151');
                                        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">';

                                        $liqCanal = function (
                                            string $titulo, string $colorHdr, string $bgHdr, string $borderClr,
                                            float $pvpBase, float $pvpPublico, bool $conIva,
                                            float $ingresoBruto, float $ingresoNeto,
                                            float $ivaTotal, float $iceTotal, float $icePorcentaje,
                                            float $utilBruta, float $utilNeta, float $margenBruto, float $margenNeto,
                                            float $roi, float $utilUnit, float $costoTot,
                                            float $matPrimas, float $moTotal, float $otrosInd
                                        ) use ($fmt, $pct, $tr2, $cantidad) {
                                            $html  = '<div style="padding:0.75rem;border:1px solid ' . $borderClr . ';border-radius:0.5rem;">';
                                            $html .= '<p style="margin:0 0 0.5rem;font-size:0.78rem;font-weight:700;color:' . $colorHdr . ';">' . $titulo . '</p>';
                                            $html .= '<table style="width:100%;">';
                                            // Precio / unidad
                                            $html .= $tr2('Precio al público / u. ' . ($conIva ? '(con IVA)' : '(sin IVA)'), '$ ' . $fmt($pvpPublico), false, $colorHdr);
                                            // Ingreso bruto
                                            $html .= $tr2('Ingreso bruto (' . number_format($cantidad, 0) . ' u.)', '$ ' . $fmt($ingresoBruto), true, $colorHdr);
                                            // IVA passthrough
                                            $html .= $tr2('(-) IVA 15% passthrough → SRI', '– $ ' . $fmt($ivaTotal), false, '#d97706', true);
                                            // = Ingreso neto
                                            $html .= $tr2('= Ingreso neto', '$ ' . $fmt($ingresoNeto), true, '#1f2937');
                                            // Costos
                                            $html .= $tr2('(-) Materias primas', '– $ ' . $fmt($matPrimas), false, '#dc2626', true);
                                            $html .= $tr2('(-) Mano de obra', '– $ ' . $fmt($moTotal), false, '#dc2626', true);
                                            $html .= $tr2('(-) Otros indirectos', '– $ ' . $fmt($otrosInd), false, '#dc2626', true);
                                            // = Utilidad bruta
                                            $html .= $tr2('= Utilidad Bruta', '$ ' . $fmt($utilBruta), true, $utilBruta >= 0 ? '#16a34a' : '#dc2626');
                                            $html .= $tr2('Margen Bruto', $pct($margenBruto), false, '#6b7280', true);
                                            if ($iceTotal > 0) {
                                                $html .= $tr2('(-) ICE (' . number_format($icePorcentaje * 100, 0) . '%)', '– $ ' . $fmt($iceTotal), false, '#d97706', true);
                                            }
                                            // = Utilidad neta
                                            $html .= $tr2('= Utilidad Neta', '$ ' . $fmt($utilNeta), true, $utilNeta >= 0 ? '#16a34a' : '#dc2626');
                                            $html .= $tr2('Margen Neto', $pct($margenNeto), false, '#6b7280', true);
                                            $html .= $tr2('Utilidad por unidad', '$ ' . $fmt($utilUnit), false, $utilNeta >= 0 ? '#16a34a' : '#dc2626');
                                            $html .= $tr2('ROI sobre costo', $pct($roi), false, '#7c3aed');
                                            $html .= '</table></div>';
                                            return $html;
                                        };

                                        $html .= $liqCanal(
                                            '🏪 Venta Directa', '#1e40af', '#eff6ff', '#bfdbfe',
                                            $pvpSinIva, $pvpConIva, $incluyeIva,
                                            $ingresoBrutoD, $ingresoD,
                                            $ivaTotalD, $iceD, $icePct,
                                            $utilBrutaD, $utilNetaD, $margenBrutaD, $margenND,
                                            $roiD, $utilUnitD, $costoTotal,
                                            $totalMat, $totalMO, $totalOtrosInd
                                        );

                                        if ($ingresoDistTiene) {
                                            $html .= $liqCanal(
                                                '🚚 Canal Distribuidores', '#4c1d95', '#f5f3ff', '#c4b5fd',
                                                $pvpDistribuidor, $pvpDistConIva, false,
                                                $ingresoBrutoDist, $ingresoDist,
                                                $ivaTotalDist, $iceDist, $icePct,
                                                $utilBrutaDist, $utilNetaDist, $margenBrutaDist, $margenNDist,
                                                $roiDist, $utilUnitDist, $costoTotal,
                                                $totalMat, $totalMO, $totalOtrosInd
                                            );
                                        } else {
                                            $html .= '<div style="padding:1rem;border:2px dashed #e5e7eb;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;">'
                                                . '<p style="margin:0;font-size:0.78rem;color:#9ca3af;">Configura el precio de distribuidor para ver esta liquidación.</p>'
                                                . '</div>';
                                        }
                                        $html .= '</div>';

                                        // ── Resumen comparativo ─────────────────────
                                        if ($ingresoDistTiene) {
                                            $difAbs = abs($difUtil);
                                            $difMsg = $difUtil >= 0
                                                ? 'El canal distribuidor genera <strong style="color:#16a34a;">$ ' . $fmt($difAbs) . ' más</strong> de utilidad neta.'
                                                : 'La venta directa genera <strong style="color:#16a34a;">$ ' . $fmt($difAbs) . ' más</strong> de utilidad neta.';
                                            $html .= '<div style="padding:0.75rem 1rem;background:#f8fafc;border:1px solid #e5e7eb;border-radius:0.5rem;margin-bottom:0.75rem;">'
                                                . '<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">'
                                                . '<p style="margin:0;font-size:0.82rem;color:#374151;">' . $difMsg . '</p>'
                                                . '<div style="display:flex;gap:0.5rem;">'
                                                . $badge($utilNetaD >= $utilNetaDist, '🏪 $ ' . $fmt($utilNetaD))
                                                . $badge($utilNetaDist >= $utilNetaD, '🚚 $ ' . $fmt($utilNetaDist))
                                                . '</div></div>'
                                                . '<p style="margin:0.5rem 0 0;font-size:0.72rem;color:#6b7280;">💡 Canal ganador: <strong style="color:' . $ganadorClr . ';">' . $ganador . '</strong>. '
                                                . 'IVA total combinado si se vende toda la producción: Directo <strong>$ ' . $fmt($ivaTotalD) . '</strong> · Dist. <strong>$ ' . $fmt($ivaTotalDist) . '</strong> (remitir al SRI).'
                                                . '</p></div>';
                                        } else {
                                            $html .= '<div style="padding:0.75rem 1rem;background:#fef3c7;border:1px solid #fcd34d;border-radius:0.5rem;">'
                                                . '<p style="margin:0;font-size:0.78rem;color:#92400e;">⚠ Configura el precio de distribuidor para ver la comparativa completa.</p>'
                                                . '</div>';
                                        }

                                        $html .= '<p style="margin-top:0.5rem;font-size:0.65rem;color:#9ca3af;">'
                                            . ($incluyeIva ? '* PVP ingresado incluía IVA. Base de cálculo (sin IVA): $ ' . $fmt($pvpSinIva) . '. ' : '')
                                            . '* IVA es passthrough: se cobra al cliente y se remite al SRI, no afecta la utilidad del productor.'
                                            . '</p>';

                                        $html .= '</div>';

                                        // ── Tabla de escenarios de viabilidad ─────────
                                        [$presEsc, $loteEsc, $capEsc, $pvpSinIvaEsc, $pvpConIvaEsc, $margenEsc, $icePctEsc, $persEsc, $costoMoEsc, $indEsc]
                                            = self::escArgs($pres, $get);
                                        $resultEsc = self::generarEscenarios($presEsc, $cantidad, $loteEsc, $capEsc, $pvpSinIvaEsc, $pvpConIvaEsc, $margenEsc, $icePctEsc, $persEsc, $costoMoEsc, $indEsc);
                                        $html .= '<div style="margin-top:1.5rem;border-top:1px solid #e5e7eb;padding-top:1.25rem;">';
                                        $html .= self::renderTablaEscenarios($resultEsc['escenarios'], $resultEsc['peQty'], $cantidad, false);
                                        $html .= '</div>';

                                        $form->fill(['_comp_html' => $html]);
                                    })
                                    ->form([
                                        \Filament\Forms\Components\Placeholder::make('_comp_html')
                                            ->label('')
                                            ->content(fn (\Filament\Forms\Get $get) => new \Illuminate\Support\HtmlString($get('_comp_html') ?? ''))
                                            ->columnSpanFull(),
                                    ]),

                                // ── Descargar PDF ─────────────────────────────────────
                                \Filament\Forms\Components\Actions\Action::make('descargar_pdf')
                                    ->label('📄 Descargar PDF')
                                    ->color('gray')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->action(function (callable $get, $record) {
                                        $presKey  = $get('_plan_presentation_id');
                                        $cantidad = (float) ($get('_plan_cantidad') ?? 0);
                                        $pres     = ($get('presentations') ?? [])[$presKey] ?? null;

                                        if (!$presKey || $cantidad <= 0 || !$pres || !$record) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Sin datos')
                                                ->body('Configura la producción antes de descargar el informe.')
                                                ->warning()->send();
                                            return;
                                        }

                                        // ── Cálculos ──────────────────────────────────
                                        $pvpCampo        = (float) ($get('_plan_pvp_venta') ?? 0);
                                        $incluyeIva      = (bool)  ($get('_plan_pvp_incluye_iva') ?? false);
                                        $pvpSinIva       = ($pvpCampo > 0 && $incluyeIva) ? round($pvpCampo / 1.15, 4) : $pvpCampo;
                                        $pvpConIva       = $incluyeIva ? $pvpCampo : round($pvpCampo * 1.15, 4);
                                        $pvpDistribuidor = (float) ($get('precio_distribuidor') ?? 0);
                                        $cantMinDist     = (int)   ($get('cantidad_minima_distribuidor') ?? 10);
                                        $margenDistPct   = (float) ($get('margen_distribuidor') ?? 40);
                                        $margenPct       = (float) ($get('_plan_margen_venta') ?? 0);
                                        $capacidad       = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                        $diasVenta       = (int)   ($get('_plan_dias_venta') ?? 0);
                                        $aplIce          = (bool)  ($get('_plan_aplica_ice') ?? false);
                                        $iceCatLabel     = $get('_plan_ice_categoria') ?? '';
                                        $icePorcentaje   = (float) ($get('_plan_ice_porcentaje') ?? 0);
                                        $icePct          = $aplIce ? $icePorcentaje / 100 : 0;
                                        $lote            = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                        $factor          = $cantidad / $lote;
                                        $presNombre      = $pres['nombre'] ?? 'Presentación';
                                        $empresa         = \Filament\Facades\Filament::getTenant();

                                        // Materiales
                                        $totalMatComprar = 0;
                                        $totalMatStock   = 0;
                                        foreach ($pres['formulaLines'] ?? [] as $line) {
                                            $item        = ($line['inventory_item_id'] ?? null) ? \App\Models\InventoryItem::find($line['inventory_item_id']) : null;
                                            $cantBase    = (float) ($line['cantidad'] ?? 0);
                                            $factorConvT = max((float) ($item?->conversion_factor ?? 1), 0.000001);
                                            $puIdT       = $item?->purchase_unit_id ?? null;
                                            $fUnitId     = $line['measurement_unit_id'] ?? null;
                                            $stockUnitIdT = $item?->measurement_unit_id;
                                            $cantNecFormula = round($cantBase * $factor, 6);
                                            if ($fUnitId == $stockUnitIdT || !$fUnitId) $cantNecStock = $cantNecFormula;
                                            elseif ($puIdT && $fUnitId == $puIdT && $puIdT != $stockUnitIdT) $cantNecStock = round($cantNecFormula * $factorConvT, 6);
                                            else $cantNecStock = round($cantNecFormula / $factorConvT, 6);
                                            [$costoPorU] = \App\Filament\App\Resources\ProductDesignResource::costoLinea($item, 1, $fUnitId);
                                            $costoTotal = $costoPorU * $cantNecStock;
                                            $enStock    = min($cantNecStock, max(0, (float) ($item?->stock_actual ?? 0)));
                                            $totalMatStock   += $costoPorU * $enStock;
                                            $totalMatComprar += $costoPorU * max(0, $cantNecStock - $enStock);
                                        }

                                        $fracMes  = $capacidad > 0 ? $cantidad / $capacidad : 0;
                                        $personas = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));
                                        $costoMo  = (float) ($get('_plan_costo_mo_persona') ?: ($get('costo_mano_obra_persona') ?? 0));
                                        $totalMO  = $personas * $costoMo * $fracMes;
                                        $totalOtrosInd = 0;
                                        foreach ($get('indirectCosts') ?? [] as $ind) {
                                            $m = (float) ($ind['monto_mensual'] ?? 0);
                                            $totalOtrosInd += match ($ind['frecuencia'] ?? 'mensual') {
                                                'semanal' => $m * 4.33 * $fracMes,
                                                'unico'   => $m,
                                                default   => $m * $fracMes,
                                            };
                                        }
                                        $totalInd    = $totalMO + $totalOtrosInd;
                                        $costoTotal  = $totalMatComprar + $totalMatStock + $totalInd;
                                        $inversionReal = $totalMatComprar + $totalInd;
                                        $costoUnitario = $cantidad > 0 ? $costoTotal / $cantidad : 0;

                                        if ($pvpSinIva <= 0 && $margenPct > 0) {
                                            $div = 1 - $margenPct / 100;
                                            $pvpSinIva = $div > 0 ? round($costoUnitario / $div, 2) : 0;
                                            $pvpConIva = round($pvpSinIva * 1.15, 4);
                                        }

                                        // Directa
                                        $pvpDistConIvaP  = round($pvpDistribuidor * 1.15, 4);
                                        $ingresoBrutoD   = $pvpConIva * $cantidad;
                                        $ingresoD        = $pvpSinIva * $cantidad;   // ingreso neto (sin IVA)
                                        $ivaUnitD        = round($pvpSinIva * 0.15, 4);
                                        $ivaD            = round($ivaUnitD * $cantidad, 2);
                                        $iceD            = round($ingresoD * $icePct, 2);
                                        $utilBrutaD      = $ingresoD - $costoTotal;
                                        $utilNetaD       = $utilBrutaD - $iceD;
                                        $margenBrutaD    = $ingresoD > 0 ? $utilBrutaD / $ingresoD * 100 : 0;
                                        $margenND        = $ingresoD > 0 ? $utilNetaD / $ingresoD * 100 : 0;
                                        $roiD            = $costoTotal > 0 ? $utilNetaD / $costoTotal * 100 : 0;
                                        $utilUnitD       = $cantidad > 0 ? $utilNetaD / $cantidad : 0;
                                        $paybackD        = ($diasVenta > 0 && $ingresoD > 0) ? (int) ceil($inversionReal / ($ingresoD / $diasVenta)) : null;

                                        // Distribuidores
                                        $ingresoBrutoDist = $pvpDistConIvaP * $cantidad;
                                        $ingresoDist      = $pvpDistribuidor * $cantidad;  // ingreso neto (sin IVA)
                                        $ivaUnitDist      = round($pvpDistribuidor * 0.15, 4);
                                        $ivaDist          = round($ivaUnitDist * $cantidad, 2);
                                        $iceDist          = round($ingresoDist * $icePct, 2);
                                        $utilBrutaDist    = $ingresoDist - $costoTotal;
                                        $utilNetaDist     = $utilBrutaDist - $iceDist;
                                        $margenBrutaDist  = $ingresoDist > 0 ? $utilBrutaDist / $ingresoDist * 100 : 0;
                                        $margenNDist      = $ingresoDist > 0 ? $utilNetaDist / $ingresoDist * 100 : 0;
                                        $roiDist          = $costoTotal > 0 ? $utilNetaDist / $costoTotal * 100 : 0;
                                        $utilUnitDist     = $cantidad > 0 ? $utilNetaDist / $cantidad : 0;
                                        $paybackDist      = ($diasVenta > 0 && $ingresoDist > 0) ? (int) ceil($inversionReal / ($ingresoDist / $diasVenta)) : null;

                                        $difUtil  = $utilNetaDist - $utilNetaD;
                                        $ganador  = $utilNetaD >= $utilNetaDist ? 'Venta Directa' : 'Canal Distribuidores';

                                        $totalMat = $totalMatStock + $totalMatComprar;

                                        $fmt  = fn ($v) => number_format((float) $v, 2);
                                        $pct  = fn ($v) => number_format((float) $v, 1) . '%';
                                        $now  = now()->format('d/m/Y H:i');

                                        // ── HTML del PDF ──────────────────────────────
                                        $clrDirecto = '#1e40af';
                                        $clrDist    = '#6d28d9';
                                        $clrGreen   = '#16a34a';
                                        $clrRed     = '#dc2626';
                                        $clrGray    = '#6b7280';

                                        $sectionTitle = fn (string $icon, string $title, string $color) =>
                                            '<h3 style="margin:1.5rem 0 0.5rem;padding:0.4rem 0.75rem;background:' . $color . '1a;border-left:4px solid ' . $color . ';font-size:0.85rem;color:' . $color . ';font-weight:700;">' . $icon . ' ' . $title . '</h3>';

                                        $trow = fn (string $label, string $v1, string $v2, bool $bold = false, string $bg = '#fff') =>
                                            '<tr style="background:' . $bg . ';">'
                                            . '<td style="padding:0.3rem 0.5rem;font-size:0.78rem;color:#374151;' . ($bold ? 'font-weight:700;' : '') . '">' . $label . '</td>'
                                            . '<td style="padding:0.3rem 0.5rem;font-size:0.78rem;text-align:right;' . ($bold ? 'font-weight:700;color:' . $clrDirecto . ';' : '') . '">' . $v1 . '</td>'
                                            . '<td style="padding:0.3rem 0.5rem;font-size:0.78rem;text-align:right;' . ($bold ? 'font-weight:700;color:' . $clrDist . ';' : '') . '">' . $v2 . '</td>'
                                            . '</tr>';

                                        $kpiBox = fn (string $title, string $value, string $sub, string $color) =>
                                            '<td style="padding:0.6rem;border:1px solid #e5e7eb;border-radius:0.25rem;text-align:center;vertical-align:top;">'
                                            . '<div style="font-size:0.62rem;color:' . $clrGray . ';text-transform:uppercase;letter-spacing:0.04em;">' . $title . '</div>'
                                            . '<div style="font-size:1rem;font-weight:700;color:' . $color . ';margin:0.15rem 0;">' . $value . '</div>'
                                            . '<div style="font-size:0.62rem;color:' . $clrGray . ';">' . $sub . '</div>'
                                            . '</td>';

                                        $html  = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
                                        $html .= '<style>
                                            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 1.5rem; }
                                            table { border-collapse: collapse; width: 100%; }
                                            td, th { vertical-align: top; }
                                            .page-break { page-break-after: always; }
                                        </style></head><body>';

                                        // Portada / Header
                                        $html .= '<div style="background:#1e3a5f;color:#fff;padding:1.25rem 1.5rem;border-radius:0.5rem;margin-bottom:1.5rem;">'
                                            . '<h1 style="margin:0;font-size:1.1rem;font-weight:700;">📊 Informe de Liquidación de Producción</h1>'
                                            . '<p style="margin:0.3rem 0 0;font-size:0.8rem;color:#bfdbfe;">'
                                            . e($record->nombre) . ' — ' . e($presNombre)
                                            . '</p>'
                                            . '<p style="margin:0.2rem 0 0;font-size:0.72rem;color:#93c5fd;">'
                                            . e($empresa->razon_social ?? $empresa->nombre ?? '') . ' · Generado: ' . $now
                                            . '</p>'
                                            . '</div>';

                                        // Etiquetas de precio según IVA
                                        $pvpLabel      = $incluyeIva ? 'PVP ingresado (IVA incluido)' : 'PVP (sin IVA)';
                                        $pvpValorMostrar = $incluyeIva
                                            ? '$ ' . $fmt($pvpConIva) . ' (= $ ' . $fmt($pvpSinIva) . ' sin IVA)'
                                            : '$ ' . $fmt($pvpSinIva);
                                        $notaIva = $incluyeIva
                                            ? '* El precio ingresado incluía IVA (15%). Se descontó para los cálculos: PVP base = $ ' . $fmt($pvpSinIva) . '.'
                                            : '* El precio ingresado no incluye IVA. IVA cobrado al cliente = $ ' . $fmt(round($pvpSinIva * 0.15, 4)) . ' por unidad.';

                                        // Parámetros
                                        $html .= $sectionTitle('⚙️', 'Parámetros de la Producción', '#374151');
                                        $html .= '<table style="width:100%;border:1px solid #e5e7eb;">';
                                        $html .= '<tr style="background:#f8fafc;"><th style="padding:0.4rem 0.6rem;font-size:0.75rem;text-align:left;">Parámetro</th><th style="padding:0.4rem 0.6rem;font-size:0.75rem;text-align:right;">Valor</th></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Presentación</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">' . e($presNombre) . '</td></tr>';
                                        $html .= '<tr style="background:#f9fafb;"><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Cantidad a producir</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">' . number_format($cantidad, 0) . ' u.</td></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">' . $pvpLabel . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">' . $pvpValorMostrar . '</td></tr>';
                                        $html .= '<tr style="background:#f9fafb;"><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Costo unitario</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($costoUnitario) . '</td></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Inversión a desembolsar</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($inversionReal) . '</td></tr>';
                                        $html .= '<tr style="background:#f9fafb;"><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Costo total producción</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($costoTotal) . '</td></tr>';
                                        if ($diasVenta > 0) $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Días de venta estimados</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">' . $diasVenta . ' días</td></tr>';
                                        $html .= '</table>';
                                        // Nota IVA
                                        $html .= '<p style="margin:0.4rem 0 0;font-size:0.68rem;color:#6b7280;font-style:italic;">' . $notaIva . '</p>';

                                        // ── Desglose de costos de producción ───────────
                                        $html .= $sectionTitle('⚙️', 'Desglose de Costos de Producción', '#374151');
                                        $html .= '<table style="width:100%;border:1px solid #e5e7eb;">';
                                        $html .= '<tr style="background:#f8fafc;"><th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:left;">Rubro</th><th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:right;">Total</th><th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:right;">Por unidad</th></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Materias primas (en stock)</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;color:#16a34a;">$ ' . $fmt($totalMatStock) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;color:#16a34a;">$ ' . $fmt($cantidad > 0 ? $totalMatStock / $cantidad : 0) . '</td></tr>';
                                        $html .= '<tr style="background:#f9fafb;"><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Materias primas (a comprar)</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;color:#dc2626;">$ ' . $fmt($totalMatComprar) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;color:#dc2626;">$ ' . $fmt($cantidad > 0 ? $totalMatComprar / $cantidad : 0) . '</td></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;font-weight:600;">Total Materias Primas</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;font-weight:600;">$ ' . $fmt($totalMat) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;font-weight:600;">$ ' . $fmt($cantidad > 0 ? $totalMat / $cantidad : 0) . '</td></tr>';
                                        $html .= '<tr style="background:#f9fafb;"><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Mano de Obra</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($totalMO) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($cantidad > 0 ? $totalMO / $cantidad : 0) . '</td></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;">Otros Costos Indirectos</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($totalOtrosInd) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($cantidad > 0 ? $totalOtrosInd / $cantidad : 0) . '</td></tr>';
                                        $html .= '<tr style="background:#f0fdf4;"><td style="padding:0.4rem 0.6rem;font-size:0.78rem;font-weight:700;">COSTO TOTAL DE PRODUCCIÓN</td><td style="padding:0.4rem 0.6rem;font-size:0.78rem;text-align:right;font-weight:700;">$ ' . $fmt($costoTotal) . '</td><td style="padding:0.4rem 0.6rem;font-size:0.78rem;text-align:right;font-weight:700;">$ ' . $fmt($costoUnitario) . '</td></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.75rem;color:#6b7280;">Inversión a desembolsar (sin stock)</td><td style="padding:0.35rem 0.6rem;font-size:0.75rem;text-align:right;color:#6b7280;">$ ' . $fmt($inversionReal) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.75rem;text-align:right;color:#6b7280;">—</td></tr>';
                                        $html .= '</table>';

                                        // ── IVA Recaudado ────────────────────────────────
                                        $html .= $sectionTitle('🧾', 'IVA Recaudado — Total por Producción', '#d97706');
                                        $html .= '<table style="width:100%;border:1px solid #fde68a;">';
                                        $html .= '<tr style="background:#fffbeb;"><th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:left;">Canal</th><th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:right;">IVA por unidad</th><th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:right;">IVA total (' . number_format($cantidad, 0) . ' u.)</th></tr>';
                                        $html .= '<tr><td style="padding:0.35rem 0.6rem;font-size:0.78rem;color:' . $clrDirecto . ';">🏪 Venta Directa</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($ivaUnitD) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;font-weight:600;color:#d97706;">$ ' . $fmt($ivaD) . '</td></tr>';
                                        if ($pvpDistribuidor > 0) {
                                            $html .= '<tr style="background:#fffbeb;"><td style="padding:0.35rem 0.6rem;font-size:0.78rem;color:' . $clrDist . ';">🚚 Canal Distribuidores</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;">$ ' . $fmt($ivaUnitDist) . '</td><td style="padding:0.35rem 0.6rem;font-size:0.78rem;text-align:right;font-weight:600;color:#d97706;">$ ' . $fmt($ivaDist) . '</td></tr>';
                                        }
                                        $html .= '</table>';
                                        $html .= '<p style="margin:0.3rem 0 0;font-size:0.68rem;color:#6b7280;font-style:italic;">↗ El IVA es passthrough: se cobra al cliente y se remite al SRI, no afecta la utilidad del productor.</p>';

                                        // ── Liquidación detallada por canal ─────────────
                                        $html .= $sectionTitle('📊', 'Liquidación Detallada por Canal', '#374151');

                                        $trLiq = fn (string $label, string $val, bool $bold = false, string $color = '#374151', bool $indent = false) =>
                                            '<tr><td style="padding:0.3rem 0.5rem;font-size:0.75rem;color:' . $color . ';' . ($bold ? 'font-weight:700;' : '') . ($indent ? 'padding-left:1rem;' : '') . '">' . $label . '</td>'
                                            . '<td style="padding:0.3rem 0.5rem;font-size:0.75rem;text-align:right;color:' . $color . ';' . ($bold ? 'font-weight:700;' : '') . '">' . $val . '</td></tr>';

                                        $liqCanalPdf = function (
                                            string $titulo, string $colorHdr, string $bgHdr, string $borderClr,
                                            float $pvpBase, float $pvpPublico, bool $conIva,
                                            float $ingresoBruto, float $ingresoNeto,
                                            float $ivaTotal, float $iceTotal, float $icePorcentaje,
                                            float $utilBruta, float $utilNeta, float $margenBruto, float $margenNeto,
                                            float $roi, float $utilUnit, float $costoTot,
                                            float $matPrimas, float $moTotal, float $otrosInd
                                        ) use ($fmt, $pct, $trLiq, $cantidad) {
                                            $h  = '<div style="padding:0.75rem;border:1px solid ' . $borderClr . ';border-radius:0.5rem;margin-bottom:0.5rem;">';
                                            $h .= '<p style="margin:0 0 0.4rem;font-size:0.8rem;font-weight:700;color:' . $colorHdr . ';">' . $titulo . '</p>';
                                            $h .= '<table style="width:100%;">';
                                            // Precio / unidad
                                            $h .= $trLiq('Precio al público / u. ' . ($conIva ? '(con IVA)' : '(sin IVA)'), '$ ' . $fmt($pvpPublico), false, $colorHdr);
                                            // Ingreso bruto
                                            $h .= $trLiq('Ingreso bruto (' . number_format($cantidad, 0) . ' u.)', '$ ' . $fmt($ingresoBruto), true, $colorHdr);
                                            // (-) IVA
                                            $h .= $trLiq('(-) IVA 15% passthrough → SRI', '– $ ' . $fmt($ivaTotal), false, '#d97706', true);
                                            // = Ingreso neto
                                            $h .= $trLiq('= Ingreso neto', '$ ' . $fmt($ingresoNeto), true, '#1f2937');
                                            // Costos
                                            $h .= $trLiq('(-) Materias primas', '– $ ' . $fmt($matPrimas), false, '#dc2626', true);
                                            $h .= $trLiq('(-) Mano de obra', '– $ ' . $fmt($moTotal), false, '#dc2626', true);
                                            $h .= $trLiq('(-) Otros indirectos', '– $ ' . $fmt($otrosInd), false, '#dc2626', true);
                                            // = Utilidad bruta
                                            $h .= $trLiq('= Utilidad Bruta', '$ ' . $fmt($utilBruta), true, $utilBruta >= 0 ? '#16a34a' : '#dc2626');
                                            $h .= $trLiq('Margen Bruto', $pct($margenBruto), false, '#6b7280', true);
                                            if ($iceTotal > 0) {
                                                $h .= $trLiq('(-) ICE (' . number_format($icePorcentaje * 100, 0) . '%)', '– $ ' . $fmt($iceTotal), false, '#d97706', true);
                                            }
                                            // = Utilidad neta
                                            $h .= $trLiq('= Utilidad Neta', '$ ' . $fmt($utilNeta), true, $utilNeta >= 0 ? '#16a34a' : '#dc2626');
                                            $h .= $trLiq('Margen Neto', $pct($margenNeto), false, '#6b7280', true);
                                            $h .= $trLiq('Utilidad por unidad', '$ ' . $fmt($utilUnit), false, $utilNeta >= 0 ? '#16a34a' : '#dc2626');
                                            $h .= $trLiq('ROI sobre costo', $pct($roi), false, '#7c3aed');
                                            $h .= $trLiq('IVA recaudado (passthrough ↗)', '$ ' . $fmt($ivaTotal), false, '#d97706');
                                            $h .= '</table></div>';
                                            return $h;
                                        };

                                        $html .= $liqCanalPdf(
                                            '🏪 Venta Directa', $clrDirecto, '#eff6ff', '#bfdbfe',
                                            $pvpSinIva, $pvpConIva, $incluyeIva,
                                            $ingresoBrutoD, $ingresoD,
                                            $ivaD, $iceD, $icePct,
                                            $utilBrutaD, $utilNetaD, $margenBrutaD, $margenND,
                                            $roiD, $utilUnitD, $costoTotal,
                                            $totalMat, $totalMO, $totalOtrosInd
                                        );

                                        if ($pvpDistribuidor > 0) {
                                            $html .= $liqCanalPdf(
                                                '🚚 Canal Distribuidores', $clrDist, '#f5f3ff', '#c4b5fd',
                                                $pvpDistribuidor, $pvpDistConIvaP, false,
                                                $ingresoBrutoDist, $ingresoDist,
                                                $ivaDist, $iceDist, $icePct,
                                                $utilBrutaDist, $utilNetaDist, $margenBrutaDist, $margenNDist,
                                                $roiDist, $utilUnitDist, $costoTotal,
                                                $totalMat, $totalMO, $totalOtrosInd
                                            );
                                        } else {
                                            $html .= '<p style="font-size:0.75rem;color:#9ca3af;font-style:italic;padding:0.5rem 0;">Precio de distribuidor no configurado — canal de distribuidores sin datos.</p>';
                                        }

                                        // ── Comparativa resumida ─────────────────────────
                                        $html .= $sectionTitle('⚖️', 'Comparativa por Canal', '#374151');
                                        $html .= '<table style="width:100%;border:1px solid #e5e7eb;">';
                                        $html .= '<tr style="background:#f8fafc;">'
                                            . '<th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:left;">Métrica</th>'
                                            . '<th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:right;color:' . $clrDirecto . ';">🏪 Venta Directa</th>'
                                            . '<th style="padding:0.4rem 0.6rem;font-size:0.72rem;text-align:right;color:' . $clrDist . ';">🚚 Distribuidores</th>'
                                            . '</tr>';
                                        $html .= $trow('Ingreso neto total', '$ ' . $fmt($ingresoD), $pvpDistribuidor > 0 ? '$ ' . $fmt($ingresoDist) : '—', true, '#f8fafc');
                                        $html .= $trow('(-) Costo total', '– $ ' . $fmt($costoTotal), '– $ ' . $fmt($costoTotal));
                                        $html .= $trow('= Utilidad Bruta', '$ ' . $fmt($utilBrutaD), $pvpDistribuidor > 0 ? '$ ' . $fmt($utilBrutaDist) : '—', false, '#f8fafc');
                                        if ($iceD > 0 || $iceDist > 0) {
                                            $html .= $trow('(-) ICE', '– $ ' . $fmt($iceD), $pvpDistribuidor > 0 ? '– $ ' . $fmt($iceDist) : '—');
                                        }
                                        $html .= $trow('= Utilidad Neta', '$ ' . $fmt($utilNetaD), $pvpDistribuidor > 0 ? '$ ' . $fmt($utilNetaDist) : '—', true, '#f0fdf4');
                                        $html .= $trow('Margen Neto', $pct($margenND), $pvpDistribuidor > 0 ? $pct($margenNDist) : '—', false, '#f8fafc');
                                        $html .= $trow('ROI', $pct($roiD), $pvpDistribuidor > 0 ? $pct($roiDist) : '—');
                                        $html .= $trow('IVA recaudado (↗ SRI)', '$ ' . $fmt($ivaD), $pvpDistribuidor > 0 ? '$ ' . $fmt($ivaDist) : '—', false, '#fffbeb');
                                        if ($paybackD !== null || $paybackDist !== null) {
                                            $html .= $trow('Payback', $paybackD !== null ? $paybackD . ' días' : '—', $paybackDist !== null ? $paybackDist . ' días' : '—');
                                        }
                                        $html .= '</table>';

                                        // Conclusión
                                        if ($pvpDistribuidor > 0) {
                                            $difAbs  = abs($difUtil);
                                            $difMsg  = $difUtil >= 0
                                                ? "El canal distribuidor genera $ {$fmt($difAbs)} más de utilidad neta."
                                                : "La venta directa genera $ {$fmt($difAbs)} más de utilidad neta.";
                                            $html .= '<div style="margin-top:1.5rem;padding:0.75rem 1rem;background:#f8fafc;border:1px solid #e5e7eb;border-radius:0.5rem;">';
                                            $html .= '<p style="margin:0;font-size:0.8rem;font-weight:700;color:#1f2937;">📋 Conclusión</p>';
                                            $html .= '<p style="margin:0.4rem 0 0;font-size:0.75rem;color:#374151;">Canal con mayor rentabilidad: <strong style="color:' . ($utilNetaD >= $utilNetaDist ? $clrDirecto : $clrDist) . ';">' . $ganador . '</strong>. ' . $difMsg . '</p>';
                                            $html .= '<p style="margin:0.3rem 0 0;font-size:0.72rem;color:#6b7280;">* IVA es passthrough: se cobra al cliente y se remite al SRI, no afecta la utilidad del productor.</p>';
                                            $html .= '</div>';
                                        }

                                        // ── Tabla de escenarios (PDF) ─────────────────
                                        $resultEscPdf = self::generarEscenarios(
                                            $pres, $cantidad, $lote, $capacidad,
                                            $pvpSinIva, $pvpConIva, $margenPct ?? 0, $icePct,
                                            $personas, $costoMo,
                                            $get('indirectCosts') ?? []
                                        );
                                        $html .= self::renderTablaEscenarios($resultEscPdf['escenarios'], $resultEscPdf['peQty'], $cantidad, true);

                                        // ══════════════════════════════════════════════
                                        // ANÁLISIS ESTRATÉGICO FINANCIERO
                                        // ══════════════════════════════════════════════
                                        $html .= '<div style="page-break-before:always;"></div>';
                                        $html .= '<div style="background:#0f172a;color:#fff;padding:1.25rem 1.5rem;border-radius:0.5rem;margin-bottom:1.5rem;">'
                                            . '<h1 style="margin:0;font-size:1rem;font-weight:700;">🧠 Análisis Estratégico Financiero</h1>'
                                            . '<p style="margin:0.3rem 0 0;font-size:0.75rem;color:#94a3b8;">'
                                            . e($record->nombre) . ' — ' . e($presNombre)
                                            . ' · ' . number_format($cantidad, 0) . ' u. · Costo/u: $ ' . $fmt($costoUnitario)
                                            . '</p></div>';

                                        // ── Tabla resumen de 4 indicadores ────────────
                                        $secAn = fn (string $title, string $color) =>
                                            '<h3 style="margin:1.5rem 0 0.4rem;padding:0.35rem 0.75rem;background:' . $color . '1a;border-left:3px solid ' . $color . ';font-size:0.82rem;color:' . $color . ';font-weight:700;">' . $title . '</h3>';

                                        $html .= $secAn('📊 Resumen de Indicadores Clave', '#374151');
                                        $html .= '<table style="width:100%;border:1px solid #e5e7eb;font-size:0.75rem;">';
                                        $html .= '<tr style="background:#f8fafc;">'
                                            . '<th style="padding:0.4rem 0.6rem;text-align:left;">Indicador</th>'
                                            . '<th style="padding:0.4rem 0.6rem;text-align:right;color:' . $clrDirecto . ';">🏪 Venta Directa</th>'
                                            . ($pvpDistribuidor > 0 ? '<th style="padding:0.4rem 0.6rem;text-align:right;color:' . $clrDist . ';">🚚 Distribuidores</th>' : '')
                                            . '<th style="padding:0.4rem 0.6rem;text-align:left;color:#6b7280;">¿Qué mide?</th>'
                                            . '</tr>';
                                        $tblRow = fn ($lbl, $v1, $v2, $desc, $bg = '#fff') =>
                                            '<tr style="background:' . $bg . ';">'
                                            . '<td style="padding:0.35rem 0.6rem;font-weight:600;">' . $lbl . '</td>'
                                            . '<td style="padding:0.35rem 0.6rem;text-align:right;">' . $v1 . '</td>'
                                            . ($pvpDistribuidor > 0 ? '<td style="padding:0.35rem 0.6rem;text-align:right;">' . $v2 . '</td>' : '')
                                            . '<td style="padding:0.35rem 0.6rem;color:#6b7280;font-size:0.72rem;">' . $desc . '</td>'
                                            . '</tr>';
                                        $html .= $tblRow('Ingreso bruto', '$ ' . $fmt($ingresoBrutoD), $pvpDistribuidor > 0 ? '$ ' . $fmt($ingresoBrutoDist) : '—', 'Tracción comercial, tamaño del mercado captado', '#f8fafc');
                                        $html .= $tblRow('Ingreso neto', '$ ' . $fmt($ingresoD), $pvpDistribuidor > 0 ? '$ ' . $fmt($ingresoDist) : '—', 'Calidad del ingreso después de IVA passthrough');
                                        $html .= $tblRow('Utilidad bruta', '$ ' . $fmt($utilBrutaD), $pvpDistribuidor > 0 ? '$ ' . $fmt($utilBrutaDist) : '—', 'Eficiencia del modelo de producción (COGS)', '#f8fafc');
                                        $html .= $tblRow('Utilidad neta', '$ ' . $fmt($utilNetaD), $pvpDistribuidor > 0 ? '$ ' . $fmt($utilNetaDist) : '—', 'Rentabilidad real tras todos los gastos');
                                        $html .= '</table>';

                                        // ── 1. Ingreso bruto ──────────────────────────
                                        $ibMejor = max($ingresoBrutoD, $ingresoBrutoDist);
                                        $traccMsj = $ibMejor >= 5000
                                            ? 'El volumen de ingresos brutos proyectado ($ ' . $fmt($ibMejor) . ') indica una tracción comercial relevante para una PYME de producción.'
                                            : 'El volumen de ingresos brutos proyectado ($ ' . $fmt($ibMejor) . ') es modesto; se requiere escalar la producción o ampliar canales para ganar masa crítica.';
                                        $html .= $secAn('1️⃣  Ingreso Bruto — Capacidad de Ventas y Tracción Comercial', '#1e40af');
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Diagnóstico:</strong> El ingreso bruto representa todo el dinero que entraría por ventas antes de cualquier deducción. Con ' . number_format($cantidad, 0) . ' unidades a $ ' . $fmt($pvpConIva) . ' c/u (con IVA), se proyecta <strong>$ ' . $fmt($ingresoBrutoD) . '</strong> en venta directa' . ($pvpDistribuidor > 0 ? ' y <strong>$ ' . $fmt($ingresoBrutoDist) . '</strong> vía distribuidores.' : '.') . '</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Interpretación estratégica:</strong> ' . $traccMsj . '</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Riesgo:</strong> Un ingreso bruto dependiente de un único canal concentra el riesgo comercial. La capacidad de producción instalada (' . ($capacidad > 0 ? number_format($capacidad, 0) . ' u./mes' : 'no definida') . ') puede convertirse en un cuello de botella si la demanda supera la oferta.</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0;"><strong>Oportunidad:</strong> Diversificar canales (directo + distribuidores) permite capturar diferentes segmentos de mercado y reducir la dependencia de un único flujo de ingresos.</p>';

                                        // ── 2. Ingreso neto ───────────────────────────
                                        $ivaRatioPct = $ingresoBrutoD > 0 ? round(($ivaD / $ingresoBrutoD) * 100, 1) : 0;
                                        $html .= $secAn('2️⃣  Ingreso Neto — Calidad del Ingreso', '#0891b2');
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Diagnóstico:</strong> Del ingreso bruto de $ ' . $fmt($ingresoBrutoD) . ', el IVA passthrough representa $ ' . $fmt($ivaD) . ' (' . $ivaRatioPct . '%), que se remite al SRI. El <strong>ingreso neto real del productor es $ ' . $fmt($ingresoD) . '</strong>.</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Interpretación estratégica:</strong> La diferencia entre ingreso bruto e ingreso neto es estructural (IVA 15%) y no depende de descuentos ni devoluciones, lo que indica que la política de precios es limpia y sin fricciones comerciales adicionales en esta simulación.</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Riesgo:</strong> Si en la práctica se aplican descuentos, cupones o devoluciones, el ingreso neto real será inferior a este proyectado, comprimiendo directamente los márgenes.</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0;"><strong>Oportunidad:</strong> Mantener una política de precios estable y minimizar descuentos preserva la calidad del ingreso. Estrategias de valor agregado (presentación, personalización) justifican el precio sin necesidad de reducciones.</p>';

                                        // ── 3. Utilidad bruta ─────────────────────────
                                        $mbDirecta = round($margenBrutaD, 1);
                                        $costoSobreIngreso = $ingresoD > 0 ? round(($costoTotal / $ingresoD) * 100, 1) : 0;
                                        $mbSalud = $mbDirecta >= 40 ? 'saludable (≥40%)' : ($mbDirecta >= 20 ? 'moderado (20-39%)' : 'ajustado (<20%)');
                                        $html .= $secAn('3️⃣  Utilidad Bruta — Eficiencia del Modelo de Producción', '#16a34a');
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Diagnóstico:</strong> Con un costo de producción de $ ' . $fmt($costoTotal) . ' ($ ' . $fmt($costoUnitario) . '/u.), la utilidad bruta en venta directa es <strong>$ ' . $fmt($utilBrutaD) . ' (' . $pct($margenBrutaD) . ' margen bruto)</strong> — nivel ' . $mbSalud . '. Los costos representan el ' . $costoSobreIngreso . '% del ingreso neto.</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Interpretación estratégica:</strong> Un margen bruto ' . $mbSalud . ' ' . ($mbDirecta >= 40 ? 'otorga capacidad suficiente para absorber costos operativos adicionales (marketing, logística, administración) sin sacrificar rentabilidad.' : 'requiere disciplina estricta en el control de costos operativos adicionales para no erosionar la rentabilidad final.') . '</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Riesgo:</strong> La mayor sensibilidad está en las materias primas ($ ' . $fmt($totalMat) . ' = ' . ($costoTotal > 0 ? round($totalMat / $costoTotal * 100, 0) : 0) . '% del costo total). Un alza del 10% en insumos reduciría la utilidad bruta en $ ' . $fmt($totalMat * 0.10) . '.</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0;"><strong>Oportunidad:</strong> Negociar volumen con proveedores, optimizar fórmulas de producción, o incrementar economías de escala puede mejorar el margen bruto sin tocar el precio de venta.</p>';

                                        // ── 4. Utilidad neta ──────────────────────────
                                        $mnSalud = $margenND >= 20 ? 'sólida (≥20%)' : ($margenND >= 10 ? 'aceptable (10-19%)' : 'ajustada (<10%)');
                                        $roiLabel = $roiD >= 50 ? 'excelente (≥50%)' : ($roiD >= 20 ? 'bueno (20-49%)' : 'bajo (<20%)');
                                        $html .= $secAn('4️⃣  Utilidad Neta — Rentabilidad Real del Negocio', '#7c3aed');
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Diagnóstico:</strong> La utilidad neta proyectada en venta directa es <strong>$ ' . $fmt($utilNetaD) . ' (' . $pct($margenND) . ' margen neto)</strong> — ' . $mnSalud . '. El ROI sobre inversión es ' . $pct($roiD) . ' — nivel ' . $roiLabel . '.' . ($paybackD !== null ? ' El payback estimado es de ' . $paybackD . ' días.' : '') . '</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Interpretación estratégica:</strong> ' . ($utilNetaD > 0 ? 'El modelo es rentable en esta proyección. La rentabilidad real del productor (excluyendo IVA passthrough) confirma que el precio de venta está bien calibrado respecto a la estructura de costos.' : 'La proyección muestra pérdida neta. Revisar urgentemente la estructura de costos o ajustar el precio de venta antes de comprometer inversión.') . '</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0 0 0.5rem;"><strong>Riesgo financiero:</strong> ' . ($margenND < 15 ? 'Margen neto estrecho — cualquier aumento en costos indirectos, logística o imprevistos puede llevar el resultado a zona negativa.' : 'Margen neto con colchón adecuado para absorber variaciones de costo sin comprometer la viabilidad.') . ($iceD > 0 ? ' El ICE (' . $pct($icePct * 100) . ') representa un gasto regulatorio de $ ' . $fmt($iceD) . ' que no puede reducirse.' : '') . '</p>';
                                        $html .= '<p style="font-size:0.78rem;color:#374151;margin:0;"><strong>Capacidad de escalamiento:</strong> Con utilidad neta de $ ' . $fmt($utilUnitD) . '/u., ' . ($utilUnitD > 0 ? 'cada unidad adicional producida dentro de la capacidad instalada genera valor incremental con costo marginal decreciente.' : 'el modelo necesita ajustes estructurales antes de escalar, ya que escalar pérdidas amplifica el daño financiero.') . '</p>';

                                        // ── Evaluación global ─────────────────────────
                                        $salud = ($utilNetaD > 0 && $margenND >= 15 && $roiD >= 20)
                                            ? ['Crecimiento Saludable', '#16a34a', 'Los indicadores muestran un modelo financiero viable con márgenes sostenibles y ROI positivo. La prioridad es escalar la producción y diversificar canales para maximizar el ingreso bruto.']
                                            : (($utilNetaD > 0 && $margenND >= 5)
                                                ? ['Estancamiento / Presión de Márgenes', '#d97706', 'El modelo es rentable pero con márgenes ajustados. Un incremento de costos o reducción de precios puede llevar al negocio a zona de pérdida. Requiere optimización urgente de la estructura de costos.']
                                                : ['Ineficiencia Estructural', '#dc2626', 'El modelo proyecta resultados insuficientes o negativos. Antes de comprometer inversión, revisar precios, reducir costos o reformular la propuesta de valor del producto.']);

                                        $html .= $secAn('🏢 Evaluación Global del Modelo de Negocio', $salud[1]);
                                        $html .= '<div style="padding:0.75rem 1rem;background:' . $salud[1] . '1a;border:1px solid ' . $salud[1] . '4d;border-radius:0.4rem;margin-bottom:0.75rem;">';
                                        $html .= '<p style="margin:0 0 0.3rem;font-size:0.82rem;font-weight:700;color:' . $salud[1] . ';">Señal detectada: ' . $salud[0] . '</p>';
                                        $html .= '<p style="margin:0;font-size:0.78rem;color:#374151;">' . $salud[2] . '</p></div>';

                                        // ── Cuellos de botella ────────────────────────
                                        $html .= $secAn('⛔ Principales Cuellos de Botella Financieros', '#dc2626');
                                        $cuellos = [];
                                        if ($totalMat / max($costoTotal, 0.01) > 0.6)
                                            $cuellos[] = '<strong>Alta concentración en materias primas</strong> (' . round($totalMat / $costoTotal * 100, 0) . '% del costo total). Cualquier volatilidad en insumos impacta directamente la rentabilidad.';
                                        if ($totalMO / max($costoTotal, 0.01) > 0.3)
                                            $cuellos[] = '<strong>Peso elevado de mano de obra</strong> (' . round($totalMO / $costoTotal * 100, 0) . '%). Escalar producción implica escalar costos laborales proporcionalmente, limitando las economías de escala.';
                                        if ($margenND < 15 && $utilNetaD > 0)
                                            $cuellos[] = '<strong>Margen neto ajustado (' . $pct($margenND) . ')</strong>. Pequeñas variaciones en precio o costo pueden erosionar totalmente la utilidad.';
                                        if ($pvpDistribuidor > 0 && $utilNetaDist < $utilNetaD * 0.6)
                                            $cuellos[] = '<strong>Canal distribuidor poco atractivo</strong>. La utilidad neta en distribuidores ($ ' . $fmt($utilNetaDist) . ') es significativamente inferior a venta directa ($ ' . $fmt($utilNetaD) . '), lo que cuestiona la viabilidad de ese canal.';
                                        if ($totalMatComprar / max($costoTotal, 0.01) > 0.4)
                                            $cuellos[] = '<strong>Alta inversión en compras de materia prima</strong> ($ ' . $fmt($totalMatComprar) . '). El capital de trabajo requerido puede limitar la frecuencia de producción.';
                                        if (empty($cuellos))
                                            $cuellos[] = 'El modelo no presenta cuellos de botella críticos evidentes en esta simulación. Se recomienda monitorear márgenes con datos reales de producción.';
                                        for ($i = 0; $i < min(3, count($cuellos)); $i++) {
                                            $html .= '<p style="font-size:0.78rem;color:#374151;margin:0.3rem 0 0;padding-left:0.5rem;border-left:3px solid #dc2626;">'. ($i+1) . '. ' . $cuellos[$i] . '</p>';
                                        }

                                        // ── Recomendaciones estratégicas ──────────────
                                        $html .= $secAn('💡 Recomendaciones Estratégicas', '#1e40af');
                                        $recs = [];
                                        // Ingresos
                                        $recs[] = ['Ingresos', $pvpDistribuidor > 0
                                            ? 'Activar ambos canales simultáneamente capturando distribuidores para volumen y venta directa para margen. Establecer mínimo de pedido distribuidor de ' . $cantMinDist . ' u. para garantizar rentabilidad.'
                                            : 'Configurar un canal de distribuidores con descuento del 30-40% sobre PVP para ampliar alcance sin incrementar costos fijos de venta directa.'];
                                        // Márgenes
                                        if ($totalMat / max($costoTotal, 0.01) > 0.5) {
                                            $recs[] = ['Márgenes', 'La materia prima representa el ' . round($totalMat / $costoTotal * 100, 0) . '% del costo. Priorizar: (1) compras por volumen con proveedores estratégicos, (2) sustitución parcial de insumos de alto costo, (3) reducción de merma en el proceso de producción.'];
                                        } else {
                                            $recs[] = ['Márgenes', 'Con un margen bruto del ' . $pct($margenBrutaD) . ', considerar incrementar el precio en un 5-10% si el mercado lo soporta — un aumento de $ ' . $fmt($pvpSinIva * 0.05) . '/u. incrementaría la utilidad neta en $ ' . $fmt($pvpSinIva * 0.05 * $cantidad) . ' por esta producción.'];
                                        }
                                        // Rentabilidad
                                        $recs[] = ['Rentabilidad', 'ROI actual: ' . $pct($roiD) . ' sobre una inversión de $ ' . $fmt($inversionReal) . '. ' . ($roiD >= 30 ? 'El nivel es atractivo. Reinvertir utilidades en ampliar capacidad instalada para escalar producción sin incrementar el margen de costo unitario.' : 'Para mejorar el ROI, enfocarse en reducir la inversión a desembolsar (mayor uso de stock disponible) y optimizar el ciclo de conversión de inventario.')];
                                        // Escalabilidad
                                        $recs[] = ['Escalabilidad', $capacidad > 0
                                            ? 'La capacidad instalada es de ' . number_format($capacidad, 0) . ' u./mes. Esta producción de ' . number_format($cantidad, 0) . ' u. representa el ' . round($cantidad / $capacidad * 100, 0) . '% de la capacidad. ' . ($cantidad / max($capacidad, 1) < 0.7 ? 'Existe margen para incrementar producción sin inversión adicional en infraestructura.' : 'Se está operando cerca del límite; para crecer se requerirá inversión en capacidad.')
                                            : 'Definir la capacidad instalada mensual en el Diseño de Producto permite calcular el punto de equilibrio operativo y planificar inversiones de expansión con datos reales.'];
                                        foreach ($recs as $rec) {
                                            $html .= '<div style="margin-bottom:0.5rem;padding:0.5rem 0.75rem;background:#f8fafc;border:1px solid #e5e7eb;border-radius:0.35rem;">';
                                            $html .= '<p style="margin:0 0 0.2rem;font-size:0.72rem;font-weight:700;color:#1e40af;text-transform:uppercase;">' . $rec[0] . '</p>';
                                            $html .= '<p style="margin:0;font-size:0.77rem;color:#374151;">' . $rec[1] . '</p></div>';
                                        }

                                        $html .= '<p style="margin:1.5rem 0 0;font-size:0.65rem;color:#9ca3af;font-style:italic;text-align:center;">Análisis generado automáticamente con base en los parámetros de simulación. No constituye asesoría financiera. Validar con datos reales de producción y ventas.</p>';

                                        $html .= '</body></html>';

                                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)
                                            ->setPaper('a4', 'portrait');

                                        $filename = 'liquidacion-' . \Illuminate\Support\Str::slug($record->nombre) . '-' . now()->format('Ymd') . '.pdf';

                                        return response()->streamDownload(
                                            fn () => print($pdf->output()),
                                            $filename,
                                            ['Content-Type' => 'application/pdf']
                                        );
                                    }),

                                // ── Guardar / Actualizar simulación ──────────────────
                                \Filament\Forms\Components\Actions\Action::make('guardar_simulacion')
                                    ->label('💾 Guardar Simulación')
                                    ->color('success')
                                    ->icon('heroicon-o-bookmark')
                                    ->mountUsing(function (\Filament\Forms\Form $form, callable $get, $record) {
                                        if (!$record) { $form->fill([]); return; }
                                        $presNombreActual = ($get('presentations') ?? [])[$get('_plan_presentation_id') ?? '']['nombre'] ?? null;
                                        $existing = \App\Models\ProductSimulation::where('product_design_id', $record->id)
                                            ->where('presentation_nombre', $presNombreActual)
                                            ->first();
                                        $form->fill([
                                            'nombre_sim'       => $existing?->nombre ?? '',
                                            'notas_sim'        => $existing?->notas ?? '',
                                            '_sim_en_proyecto' => ($existing?->estado === 'en_proyecto') ? '1' : '0',
                                            '_sim_es_nueva'    => $existing ? '0' : '1',
                                        ]);
                                    })
                                    ->form([
                                        // Aviso bloqueo
                                        \Filament\Forms\Components\Placeholder::make('_aviso_bloqueada')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div style="padding:0.75rem 1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:0.5rem;color:#991b1b;font-size:0.82rem;">'
                                                . '🔒 <strong>Simulación en ejecución.</strong> No se puede modificar mientras tenga estado <em>En Proyecto</em>.'
                                                . '</div>'
                                            ))
                                            ->visible(fn (\Filament\Forms\Get $get) => $get('_sim_en_proyecto') === '1')
                                            ->columnSpanFull(),

                                        // Badge informativo (nueva vs actualización)
                                        \Filament\Forms\Components\Placeholder::make('_aviso_tipo')
                                            ->label('')
                                            ->content(fn (\Filament\Forms\Get $get) => new \Illuminate\Support\HtmlString(
                                                $get('_sim_es_nueva') === '1'
                                                    ? '<span style="font-size:0.75rem;color:#16a34a;">✦ Se creará una nueva simulación para esta presentación.</span>'
                                                    : '<span style="font-size:0.75rem;color:#d97706;">✦ Ya existe una simulación para esta presentación. Se actualizarán los datos.</span>'
                                            ))
                                            ->visible(fn (\Filament\Forms\Get $get) => $get('_sim_en_proyecto') !== '1')
                                            ->columnSpanFull(),

                                        \Filament\Forms\Components\TextInput::make('nombre_sim')
                                            ->label('Nombre de la Simulación')
                                            ->required()
                                            ->placeholder('Ej: Producción Octubre 2026 — Escenario A')
                                            ->maxLength(150)
                                            ->disabled(fn (\Filament\Forms\Get $get) => $get('_sim_en_proyecto') === '1')
                                            ->dehydrated(),

                                        \Filament\Forms\Components\Textarea::make('notas_sim')
                                            ->label('Notas opcionales')
                                            ->rows(2)
                                            ->disabled(fn (\Filament\Forms\Get $get) => $get('_sim_en_proyecto') === '1')
                                            ->dehydrated(),

                                        // Campos ocultos de control
                                        \Filament\Forms\Components\Hidden::make('_sim_en_proyecto'),
                                        \Filament\Forms\Components\Hidden::make('_sim_es_nueva'),
                                    ])
                                    ->modalSubmitActionLabel(fn (array $arguments) => '💾 Guardar')
                                    ->action(function (array $data, callable $get, $record) {
                                        // ── Bloquear si en_proyecto ────────────────────
                                        if (($data['_sim_en_proyecto'] ?? '0') === '1') {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Simulación bloqueada')
                                                ->body('No se puede modificar una simulación que está en estado "En Proyecto".')
                                                ->danger()->send();
                                            return;
                                        }

                                        $presKey   = $get('_plan_presentation_id');
                                        $cantidad  = (float) ($get('_plan_cantidad') ?? 0);
                                        $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                        $pres      = ($get('presentations') ?? [])[$presKey] ?? null;
                                        if (!$presKey || $cantidad <= 0 || !$pres || !$record) return;

                                        $pvpSinIva  = (float) ($get('_plan_pvp_venta') ?? 0);
                                        $margenPct  = (float) ($get('_plan_margen_venta') ?? 0);
                                        $lote       = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                        $factor     = $cantidad / $lote;

                                        $totalMat = 0;
                                        foreach ($pres['formulaLines'] ?? [] as $line) {
                                            $item = ($line['inventory_item_id'] ?? null) ? \App\Models\InventoryItem::find($line['inventory_item_id']) : null;
                                            $cantBase = (float) ($line['cantidad'] ?? 0);
                                            $factorConvT = max((float) ($item?->conversion_factor ?? 1), 0.000001);
                                            $puIdT = $item?->purchase_unit_id ?? null;
                                            $fUnitId = $line['measurement_unit_id'] ?? null;
                                            $stockUnitIdT = $item?->measurement_unit_id;
                                            $cantNecFormula = round($cantBase * $factor, 6);
                                            if ($fUnitId == $stockUnitIdT || !$fUnitId) $cantNecStock = $cantNecFormula;
                                            elseif ($puIdT && $fUnitId == $puIdT && $puIdT != $stockUnitIdT) $cantNecStock = round($cantNecFormula * $factorConvT, 6);
                                            else $cantNecStock = round($cantNecFormula / $factorConvT, 6);
                                            [$costoPorU] = \App\Filament\App\Resources\ProductDesignResource::costoLinea($item, 1, $fUnitId);
                                            $totalMat += $costoPorU * $cantNecStock;
                                        }
                                        $fracMes    = $capacidad > 0 ? $cantidad / $capacidad : 0;
                                        $personas   = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));
                                        $costoMo    = (float) ($get('_plan_costo_mo_persona') ?: ($get('costo_mano_obra_persona') ?? 0));
                                        $totalInd   = $personas * $costoMo * $fracMes;
                                        foreach ($get('indirectCosts') ?? [] as $ind) {
                                            $m = (float) ($ind['monto_mensual'] ?? 0);
                                            $totalInd += match ($ind['frecuencia'] ?? 'mensual') {
                                                'semanal' => $m * 4.33 * $fracMes,
                                                'unico'   => $m,
                                                default   => $m * $fracMes,
                                            };
                                        }
                                        $costoTotal    = $totalMat + $totalInd;
                                        $costoUnitario = $cantidad > 0 ? $costoTotal / $cantidad : 0;
                                        if ($pvpSinIva <= 0) {
                                            if ($margenPct <= 0) $margenPct = (float) ($pres['margen_objetivo'] ?? 30);
                                            $div = 1 - $margenPct / 100;
                                            $pvpSinIva = ($div > 0 && $costoUnitario > 0) ? round($costoUnitario / $div, 2) : 0;
                                        }
                                        $ingresoNeto   = $pvpSinIva * $cantidad;
                                        $ivaTotal      = round($ingresoNeto * 0.15, 2);
                                        $icePct        = (bool) ($get('_plan_aplica_ice') ?? false) ? (float) ($get('_plan_ice_porcentaje') ?? 0) / 100 : 0;
                                        $iceTotal      = round($ingresoNeto * $icePct, 2);
                                        $utilBruta     = $ingresoNeto - $costoTotal;
                                        $utilNeta      = $utilBruta - $iceTotal;
                                        $margenBruto   = $ingresoNeto > 0 ? ($utilBruta / $ingresoNeto) * 100 : 0;
                                        $margenNeto    = $ingresoNeto > 0 ? ($utilNeta / $ingresoNeto) * 100 : 0;
                                        $roi           = $costoTotal > 0 ? ($utilNeta / $costoTotal) * 100 : 0;
                                        $diasVenta     = (int) ($get('_plan_dias_venta') ?? 0);
                                        $ingresoDiario = $diasVenta > 0 ? $ingresoNeto / $diasVenta : 0;
                                        $payback       = $ingresoDiario > 0 ? round($costoTotal / $ingresoDiario, 1) : null;
                                        $presNombre    = $pres['nombre'] ?? null;

                                        $payload = [
                                            'nombre'             => $data['nombre_sim'],
                                            'cantidad'           => $cantidad,
                                            'pvp_sin_iva'        => $pvpSinIva,
                                            'margen_porcentaje'  => $margenPct,
                                            'dias_venta'         => $diasVenta,
                                            'meta_ganancia'      => (float) ($get('_plan_meta_ganancia') ?? 5),
                                            'aplica_ice'         => (bool) ($get('_plan_aplica_ice') ?? false),
                                            'ice_categoria'      => $get('_plan_ice_categoria'),
                                            'ice_porcentaje'     => (float) ($get('_plan_ice_porcentaje') ?? 0),
                                            'inversion_real'     => $costoTotal,
                                            'costo_total'        => $costoTotal,
                                            'ingreso_neto'       => $ingresoNeto,
                                            'utilidad_bruta'     => $utilBruta,
                                            'utilidad_neta'      => $utilNeta,
                                            'margen_bruto'       => round($margenBruto, 2),
                                            'margen_neto'        => round($margenNeto, 2),
                                            'roi'                => round($roi, 2),
                                            'payback_dias'       => $payback,
                                            'iva_total'          => $ivaTotal,
                                            'ice_total'          => $iceTotal,
                                            'notas'              => $data['notas_sim'] ?? null,
                                        ];

                                        // ── Upsert: una simulación por diseño + presentación ──
                                        $empresaId = \Filament\Facades\Filament::getTenant()->id;
                                        $existing  = \App\Models\ProductSimulation::where('empresa_id', $empresaId)
                                            ->where('product_design_id', $record->id)
                                            ->where('presentation_nombre', $presNombre)
                                            ->first();

                                        $esNueva = !$existing;
                                        if ($existing) {
                                            $existing->update($payload);
                                        } else {
                                            \App\Models\ProductSimulation::create(array_merge($payload, [
                                                'empresa_id'          => $empresaId,
                                                'product_design_id'   => $record->id,
                                                'presentation_nombre' => $presNombre,
                                                'estado'              => 'borrador',
                                            ]));
                                        }

                                        \Filament\Notifications\Notification::make()
                                            ->title($esNueva ? 'Simulación creada' : 'Simulación actualizada')
                                            ->body($esNueva
                                                ? "La simulación \"{$data['nombre_sim']}\" fue creada exitosamente."
                                                : "La simulación \"{$data['nombre_sim']}\" fue actualizada con los datos actuales.")
                                            ->success()->send();
                                    }),
                            ])->columnSpanFull(),
                        ]),
                    Tab::make('Punto de Equilibrio')
                        ->icon('heroicon-o-scale')
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('_equilibrio_panel')
                                ->label('')
                                ->content(function (callable $get): \Illuminate\Support\HtmlString {
                                    $fmt = fn ($v) => number_format((float) $v, 2);
                                    $pct = fn ($v) => number_format((float) $v, 1) . '%';
                                    $tenant = \Filament\Facades\Filament::getTenant();

                                    // ── Costos fijos de empresa ──
                                    $costosFijos = $tenant
                                        ? \App\Models\CostoFijo::where('empresa_id', $tenant->id)->where('activo', true)->get()
                                        : collect();
                                    $totalFijosMensual = $costosFijos->sum(fn ($cf) => $cf->monto_mensual);

                                    // ── Costo unitario de producto ──
                                    $presKey   = $get('_plan_presentation_id');
                                    $cantidad  = (float) ($get('_plan_cantidad') ?? 0);
                                    $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                    $pres      = ($get('presentations') ?? [])[$presKey] ?? null;

                                    if (!$pres || $cantidad <= 0) {
                                        $html = '<div style="padding:2rem;text-align:center;border:2px dashed #e5e7eb;border-radius:0.75rem;color:#6b7280;">'
                                            . '<p style="font-size:0.9rem;margin-bottom:0.5rem;">⚖️ Configura la simulación primero</p>'
                                            . '<p style="font-size:0.78rem;">Selecciona una presentación y cantidad en la pestaña <strong>Simulación y Análisis</strong>.</p>'
                                            . '</div>';

                                        // Mostrar tabla de fijos aunque no haya simulación
                                        if ($costosFijos->isNotEmpty()) {
                                            $html .= '<div style="margin-top:1.5rem;">';
                                            $html .= self::renderTablaCostosFijos($costosFijos, $totalFijosMensual);
                                            $html .= '</div>';
                                        }
                                        return new \Illuminate\Support\HtmlString($html);
                                    }

                                    $lote   = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                    $factor = $cantidad / $lote;
                                    $fracMes = $capacidad > 0 ? $cantidad / $capacidad : 0;

                                    // ── Materiales ──
                                    $totalMat = 0;
                                    foreach ($pres['formulaLines'] ?? [] as $line) {
                                        $item = ($line['inventory_item_id'] ?? null) ? \App\Models\InventoryItem::find($line['inventory_item_id']) : null;
                                        $cantBase = (float) ($line['cantidad'] ?? 0);
                                        $factorConv = max((float) ($item?->conversion_factor ?? 1), 0.000001);
                                        $puId = $item?->purchase_unit_id ?? null;
                                        $fUnitId = $line['measurement_unit_id'] ?? null;
                                        $stockUnitId = $item?->measurement_unit_id;
                                        $cantNecF = round($cantBase * $factor, 6);
                                        if ($fUnitId == $stockUnitId || !$fUnitId) $cantNecS = $cantNecF;
                                        elseif ($puId && $fUnitId == $puId && $puId != $stockUnitId) $cantNecS = round($cantNecF * $factorConv, 6);
                                        else $cantNecS = round($cantNecF / $factorConv, 6);
                                        [$cPorU] = self::costoLinea($item, 1, $fUnitId);
                                        $totalMat += $cPorU * $cantNecS;
                                    }

                                    // ── MO + Indirectos ──
                                    $personas = (float) ($get('num_personas') ?? 0);
                                    $costoMo  = (float) ($get('costo_mano_obra_persona') ?? 0);
                                    $totalMO  = $personas * $costoMo * $fracMes;
                                    $totalOtrosInd = 0;
                                    foreach ($get('indirectCosts') ?? [] as $ind) {
                                        $m = (float) ($ind['monto_mensual'] ?? 0);
                                        $totalOtrosInd += match ($ind['frecuencia'] ?? 'mensual') {
                                            'semanal' => $m * 4.33 * $fracMes,
                                            'unico'   => $m,
                                            default   => $m * $fracMes,
                                        };
                                    }

                                    // ── Costos fijos prorrateados ──
                                    $totalFijosProrr = $totalFijosMensual * $fracMes;

                                    // ── Totales ──
                                    $costoVariable   = $totalMat + $totalMO + $totalOtrosInd;
                                    $costoTotal      = $costoVariable + $totalFijosProrr;
                                    $costoUnitario   = $cantidad > 0 ? $costoTotal / $cantidad : 0;
                                    $costoVarUnit    = $cantidad > 0 ? $costoVariable / $cantidad : 0;
                                    $costoFijoUnit   = $cantidad > 0 ? $totalFijosProrr / $cantidad : 0;
                                    $matUnit         = $cantidad > 0 ? $totalMat / $cantidad : 0;
                                    $moUnit          = $cantidad > 0 ? $totalMO / $cantidad : 0;
                                    $indUnit         = $cantidad > 0 ? $totalOtrosInd / $cantidad : 0;

                                    // ── PVP ──
                                    $incluyeIva = (bool) ($get('_plan_pvp_incluye_iva') ?? false);
                                    $pvpCampo   = (float) ($get('_plan_pvp_venta') ?? 0);
                                    if ($pvpCampo > 0) {
                                        $pvpSinIva = $incluyeIva ? round($pvpCampo / 1.15, 4) : $pvpCampo;
                                    } else {
                                        $margenPct = (float) ($get('_plan_margen_venta') ?? 0);
                                        if ($margenPct <= 0) $margenPct = (float) ($pres['margen_objetivo'] ?? 30);
                                        $div = 1 - ($margenPct / 100);
                                        $pvpSinIva = ($div > 0 && $costoUnitario > 0) ? round($costoUnitario / $div, 2) : 0;
                                    }
                                    $pvpConIva      = round($pvpSinIva * 1.15, 2);
                                    $margenUnit     = $pvpSinIva - $costoUnitario;
                                    $margenPctCalc  = $pvpSinIva > 0 ? ($margenUnit / $pvpSinIva) * 100 : 0;
                                    $ivaUnit        = round($pvpSinIva * 0.15, 4);

                                    // ── Contribución y Punto de Equilibrio ──
                                    $contribucionUnit = $pvpSinIva - $costoVarUnit; // margen de contribución
                                    $peUnidades       = $contribucionUnit > 0 ? ceil($totalFijosMensual / $contribucionUnit) : null;
                                    $peMonetario      = $peUnidades !== null ? $peUnidades * $pvpSinIva : null;
                                    $coberturaPct     = ($peUnidades !== null && $peUnidades > 0) ? min(($cantidad / $peUnidades) * 100, 999) : null;

                                    // Distribuidor
                                    $pvpDist        = (float) ($get('precio_distribuidor') ?? 0);
                                    $contribDistUnit = $pvpDist - $costoVarUnit;
                                    $peUnidadesDist  = ($pvpDist > 0 && $contribDistUnit > 0) ? ceil($totalFijosMensual / $contribDistUnit) : null;

                                    // ── RENDER HTML ──
                                    $cBlue  = '#2563eb'; $cGreen = '#16a34a'; $cRed = '#dc2626';
                                    $cAmb   = '#d97706'; $cPurp  = '#7c3aed'; $cGray = '#6b7280';

                                    $html = '';

                                    // ══════════════════════════════════════════════════
                                    // 1. ESTRUCTURA DEL PVP
                                    // ══════════════════════════════════════════════════
                                    $html .= '<div style="margin-bottom:2rem;">';
                                    $html .= '<div style="margin-bottom:0.75rem;padding:0.6rem 1rem;background:#f0fdf4;border-radius:0.5rem;border:1px solid #bbf7d0;">'
                                        . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#15803d;">💰 COMPOSICIÓN DEL PRECIO DE VENTA</span>'
                                        . '<span style="float:right;font-size:0.7rem;color:#16a34a;">PVP: $ ' . $fmt($pvpConIva) . ' (con IVA)</span>'
                                        . '</div>';

                                    // Barra visual proporcional
                                    $parts = [
                                        ['Materiales', $matUnit, '#ef4444'],
                                        ['M.O.', $moUnit, '#f97316'],
                                        ['Indirectos', $indUnit, '#eab308'],
                                        ['Fijos Empresa', $costoFijoUnit, '#8b5cf6'],
                                        ['Margen', max($margenUnit, 0), '#22c55e'],
                                    ];
                                    $totalBar = $pvpSinIva > 0 ? $pvpSinIva : 1;

                                    $html .= '<div style="display:flex;height:2rem;border-radius:0.5rem;overflow:hidden;margin-bottom:0.5rem;border:1px solid #e5e7eb;">';
                                    foreach ($parts as $p) {
                                        $w = ($p[1] / $totalBar) * 100;
                                        if ($w < 0.5) continue;
                                        $html .= '<div style="width:' . number_format($w, 1) . '%;background:' . $p[2] . ';display:flex;align-items:center;justify-content:center;">'
                                            . '<span style="font-size:0.55rem;color:#fff;font-weight:700;text-shadow:0 1px 2px rgba(0,0,0,0.3);white-space:nowrap;">' . $p[0] . '</span>'
                                            . '</div>';
                                    }
                                    $html .= '</div>';

                                    // Leyenda
                                    $html .= '<div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;">';
                                    foreach ($parts as $p) {
                                        $pctPvp = $pvpSinIva > 0 ? ($p[1] / $pvpSinIva) * 100 : 0;
                                        $html .= '<span style="font-size:0.68rem;color:#374151;">'
                                            . '<span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:' . $p[2] . ';margin-right:4px;"></span>'
                                            . $p[0] . ': $ ' . number_format($p[1], 4) . ' (' . number_format($pctPvp, 1) . '%)'
                                            . '</span>';
                                    }
                                    $html .= '</div>';

                                    // Tabla detallada
                                    $html .= '<table style="width:100%;border-collapse:collapse;font-size:0.78rem;">';
                                    $thStyle = 'style="padding:0.4rem 0.75rem;background:#f8fafc;border-bottom:2px solid #e5e7eb;text-align:left;font-size:0.68rem;color:#475569;text-transform:uppercase;letter-spacing:0.05em;"';
                                    $html .= '<thead><tr><th ' . $thStyle . '>Componente</th><th ' . $thStyle . ' style="text-align:right;">$/Unidad</th><th ' . $thStyle . ' style="text-align:right;">% del PVP</th><th ' . $thStyle . ' style="text-align:right;">Total (' . number_format($cantidad, 0) . ' u.)</th></tr></thead>';
                                    $html .= '<tbody>';

                                    $row = function (string $label, float $perUnit, string $color = '', bool $bold = false, bool $indent = false) use ($pvpSinIva, $cantidad, $fmt) {
                                        $pctPvp = $pvpSinIva > 0 ? ($perUnit / $pvpSinIva) * 100 : 0;
                                        $total  = $perUnit * $cantidad;
                                        $fw = $bold ? 'font-weight:700;' : '';
                                        $cl = $color ? 'color:' . $color . ';' : '';
                                        $pl = $indent ? 'padding-left:1.5rem;' : '';
                                        return '<tr style="border-bottom:1px solid #f1f5f9;">'
                                            . '<td style="padding:0.4rem 0.75rem;' . $fw . $cl . $pl . '">' . $label . '</td>'
                                            . '<td style="padding:0.4rem 0.75rem;text-align:right;font-family:monospace;' . $fw . $cl . '">$ ' . number_format($perUnit, 4) . '</td>'
                                            . '<td style="padding:0.4rem 0.75rem;text-align:right;' . $fw . $cl . '">' . number_format($pctPvp, 1) . '%</td>'
                                            . '<td style="padding:0.4rem 0.75rem;text-align:right;font-family:monospace;' . $fw . $cl . '">$ ' . $fmt($total) . '</td>'
                                            . '</tr>';
                                    };

                                    $html .= $row('Materias primas', $matUnit, '#ef4444', false, true);
                                    $html .= $row('Mano de obra', $moUnit, '#f97316', false, true);
                                    $html .= $row('Costos indirectos producto', $indUnit, '#eab308', false, true);
                                    $html .= $row('Costos fijos empresa', $costoFijoUnit, '#8b5cf6', false, true);
                                    $html .= '<tr style="border-top:2px solid #e5e7eb;background:#f8fafc;">';
                                    $html .= $row('= Costo Total', $costoUnitario, $cRed, true);
                                    $html .= $row('+ Margen (' . number_format($margenPctCalc, 1) . '%)', $margenUnit, $cGreen, true);
                                    $html .= '<tr style="border-top:2px solid #1e293b;background:#1e293b;">';
                                    $html .= '<td style="padding:0.5rem 0.75rem;font-weight:700;color:#fff;">= PVP sin IVA</td>'
                                        . '<td style="padding:0.5rem 0.75rem;text-align:right;font-family:monospace;font-weight:700;color:#fff;">$ ' . number_format($pvpSinIva, 4) . '</td>'
                                        . '<td style="padding:0.5rem 0.75rem;text-align:right;font-weight:700;color:#fff;">100%</td>'
                                        . '<td style="padding:0.5rem 0.75rem;text-align:right;font-family:monospace;font-weight:700;color:#fff;">$ ' . $fmt($pvpSinIva * $cantidad) . '</td>';
                                    $html .= '</tr>';
                                    $html .= $row('+ IVA 15%', $ivaUnit, $cGray);
                                    $html .= '<tr style="background:#f0fdf4;">';
                                    $html .= '<td style="padding:0.5rem 0.75rem;font-weight:700;color:' . $cGreen . ';">= PVP al Público</td>'
                                        . '<td style="padding:0.5rem 0.75rem;text-align:right;font-family:monospace;font-weight:700;color:' . $cGreen . ';">$ ' . number_format($pvpConIva, 4) . '</td>'
                                        . '<td style="padding:0.5rem 0.75rem;text-align:right;font-weight:700;color:' . $cGreen . ';">115%</td>'
                                        . '<td style="padding:0.5rem 0.75rem;text-align:right;font-family:monospace;font-weight:700;color:' . $cGreen . ';">$ ' . $fmt($pvpConIva * $cantidad) . '</td>';
                                    $html .= '</tr>';
                                    $html .= '</tbody></table>';
                                    $html .= '</div>';

                                    // ══════════════════════════════════════════════════
                                    // 2. COSTOS FIJOS DE LA EMPRESA
                                    // ══════════════════════════════════════════════════
                                    $html .= self::renderTablaCostosFijos($costosFijos, $totalFijosMensual);

                                    // ══════════════════════════════════════════════════
                                    // 3. PUNTO DE EQUILIBRIO
                                    // ══════════════════════════════════════════════════
                                    $html .= '<div style="margin-top:2rem;">';
                                    $html .= '<div style="margin-bottom:0.75rem;padding:0.6rem 1rem;background:#fef3c7;border-radius:0.5rem;border:1px solid #fde68a;">'
                                        . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#92400e;">⚖️ PUNTO DE EQUILIBRIO</span>'
                                        . '</div>';

                                    // KPIs
                                    $kpi = function (string $label, string $value, string $sub, string $color, bool $last = false) {
                                        $border = $last ? '' : 'border-right:1px solid #f1f5f9;';
                                        return '<div style="padding:1rem;text-align:center;' . $border . '">'
                                            . '<p style="font-size:0.62rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.2rem;">' . $label . '</p>'
                                            . '<p style="font-size:1.5rem;font-weight:800;color:' . $color . ';line-height:1.1;">' . $value . '</p>'
                                            . '<p style="font-size:0.65rem;color:#9ca3af;margin-top:0.15rem;">' . $sub . '</p>'
                                            . '</div>';
                                    };

                                    $html .= '<div style="display:grid;grid-template-columns:repeat(4,1fr);border:1px solid #e5e7eb;border-radius:0.75rem;overflow:hidden;margin-bottom:1.25rem;">';

                                    // Contribución por unidad
                                    $html .= $kpi(
                                        'Contribución / unidad',
                                        '$ ' . number_format($contribucionUnit, 4),
                                        'PVP sin IVA − Costo variable',
                                        $contribucionUnit > 0 ? $cGreen : $cRed
                                    );

                                    // PE en unidades
                                    $html .= $kpi(
                                        'Equilibrio (unidades)',
                                        $peUnidades !== null ? number_format($peUnidades) . ' u.' : '—',
                                        $peUnidades !== null ? 'Para cubrir $ ' . $fmt($totalFijosMensual) . ' /mes de fijos' : 'Contribución ≤ 0',
                                        $cAmb
                                    );

                                    // PE monetario
                                    $html .= $kpi(
                                        'Equilibrio (monetario)',
                                        $peMonetario !== null ? '$ ' . $fmt($peMonetario) : '—',
                                        'Ventas mensuales necesarias',
                                        $cBlue
                                    );

                                    // Cobertura
                                    $cobColor = $coberturaPct === null ? $cGray : ($coberturaPct >= 100 ? $cGreen : ($coberturaPct >= 50 ? $cAmb : $cRed));
                                    $html .= $kpi(
                                        'Cobertura esta producción',
                                        $coberturaPct !== null ? number_format($coberturaPct, 1) . '%' : '—',
                                        number_format($cantidad, 0) . ' u. planificadas de ' . ($peUnidades !== null ? number_format($peUnidades) : '?') . ' necesarias',
                                        $cobColor,
                                        true
                                    );
                                    $html .= '</div>';

                                    // Barra de progreso visual
                                    if ($coberturaPct !== null) {
                                        $barPct = min($coberturaPct, 100);
                                        $barColor = $coberturaPct >= 100 ? '#22c55e' : ($coberturaPct >= 50 ? '#f59e0b' : '#ef4444');
                                        $html .= '<div style="margin-bottom:1.25rem;">';
                                        $html .= '<div style="display:flex;justify-content:space-between;font-size:0.68rem;color:#6b7280;margin-bottom:0.3rem;">'
                                            . '<span>0%</span><span>Punto de Equilibrio (100%)</span>'
                                            . '</div>';
                                        $html .= '<div style="position:relative;height:1.5rem;background:#f1f5f9;border-radius:999px;overflow:hidden;">';
                                        $html .= '<div style="height:100%;width:' . number_format($barPct, 1) . '%;background:' . $barColor . ';border-radius:999px;transition:width 0.5s;display:flex;align-items:center;justify-content:center;">';
                                        if ($barPct > 15) {
                                            $html .= '<span style="font-size:0.6rem;font-weight:700;color:#fff;">' . number_format($coberturaPct, 1) . '%</span>';
                                        }
                                        $html .= '</div></div>';
                                        if ($coberturaPct >= 100) {
                                            $html .= '<p style="margin-top:0.5rem;font-size:0.75rem;color:' . $cGreen . ';font-weight:600;">✓ Esta producción cubre el punto de equilibrio y genera utilidad adicional.</p>';
                                        } else {
                                            $faltantes = $peUnidades !== null ? $peUnidades - $cantidad : 0;
                                            $html .= '<p style="margin-top:0.5rem;font-size:0.75rem;color:' . $cAmb . ';">⚠ Faltan <strong>' . number_format(max($faltantes, 0)) . ' unidades</strong> para alcanzar el punto de equilibrio. Necesitas otros productos o más volumen.</p>';
                                        }
                                        $html .= '</div>';
                                    }

                                    // ── Comparativa por canal ──
                                    if ($pvpDist > 0 && $peUnidades !== null) {
                                        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.25rem;">';

                                        // Venta directa
                                        $html .= '<div style="padding:1rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.75rem;">'
                                            . '<p style="font-size:0.72rem;font-weight:700;color:#1e40af;margin-bottom:0.5rem;">🏪 Venta Directa</p>'
                                            . '<p style="font-size:0.68rem;color:#374151;">Contribución/u.: <strong>$ ' . number_format($contribucionUnit, 4) . '</strong></p>'
                                            . '<p style="font-size:0.68rem;color:#374151;">Equilibrio: <strong>' . number_format($peUnidades) . ' unidades</strong></p>'
                                            . '<p style="font-size:0.68rem;color:#374151;">Ventas: <strong>$ ' . $fmt($peMonetario) . '</strong></p>'
                                            . '</div>';

                                        // Distribuidores
                                        $html .= '<div style="padding:1rem;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:0.75rem;">'
                                            . '<p style="font-size:0.72rem;font-weight:700;color:#6d28d9;margin-bottom:0.5rem;">🚚 Distribuidores</p>'
                                            . '<p style="font-size:0.68rem;color:#374151;">Contribución/u.: <strong>$ ' . number_format($contribDistUnit, 4) . '</strong></p>'
                                            . '<p style="font-size:0.68rem;color:#374151;">Equilibrio: <strong>' . ($peUnidadesDist !== null ? number_format($peUnidadesDist) . ' unidades' : 'N/A') . '</strong></p>'
                                            . '<p style="font-size:0.68rem;color:#374151;">Ventas: <strong>' . ($peUnidadesDist !== null ? '$ ' . $fmt($peUnidadesDist * $pvpDist) : 'N/A') . '</strong></p>'
                                            . '</div>';

                                        $html .= '</div>';
                                    }

                                    // Nota explicativa
                                    $html .= '<p style="font-size:0.68rem;color:' . $cGray . ';line-height:1.6;">'
                                        . '* <strong>Punto de equilibrio</strong>: cantidad mínima mensual para cubrir los costos fijos de la empresa con la contribución de este producto. '
                                        . '* <strong>Margen de contribución</strong>: PVP sin IVA − costo variable unitario (materiales + MO + indirectos del producto). '
                                        . '* Los costos fijos se prorratean según la fracción de capacidad utilizada (cantidad / capacidad mensual).'
                                        . '</p>';

                                    $html .= '</div>';

                                    // ══════════════════════════════════════════════════
                                    // COMPROMISOS FINANCIEROS (Deudas + Costos Fijos)
                                    // ══════════════════════════════════════════════════
                                    if ($contribucionUnit > 0 && $tenant) {
                                        $servicioDeudasMes = (float) \App\Models\DebtAmortizationLine::whereHas(
                                            'debt', fn ($q) => $q->where('empresa_id', $tenant->id)
                                        )
                                        ->whereMonth('fecha_vencimiento', now()->month)
                                        ->whereYear('fecha_vencimiento', now()->year)
                                        ->where('estado', '!=', 'pagada')
                                        ->sum('total_cuota');

                                        $totalCompromisos    = $totalFijosMensual + $servicioDeudasMes;
                                        $contribucionTotal   = $contribucionUnit * $cantidad;
                                        $peTotalUnidades     = $totalCompromisos > 0 ? ceil($totalCompromisos / $contribucionUnit) : null;
                                        $peTotalMonetario    = $peTotalUnidades !== null ? $peTotalUnidades * $pvpSinIva : null;
                                        $coberturaTotal      = ($peTotalUnidades !== null && $peTotalUnidades > 0)
                                            ? min(($cantidad / $peTotalUnidades) * 100, 999)
                                            : null;
                                        $pctAporte           = $totalCompromisos > 0
                                            ? min(($contribucionTotal / $totalCompromisos) * 100, 999)
                                            : 0;

                                        $colorAporte = $pctAporte >= 100 ? $cGreen : ($pctAporte >= 50 ? $cAmb : $cRed);

                                        $html .= '<div style="margin-top:1.5rem;">';
                                        $html .= '<div style="margin-bottom:0.75rem;padding:0.6rem 1rem;background:#eff6ff;border-radius:0.5rem;border:1px solid #bfdbfe;">'
                                            . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#1d4ed8;">💳 COMPROMISOS FINANCIEROS DEL MES</span>'
                                            . '<span style="float:right;font-size:0.7rem;color:#2563eb;">Total: $ ' . $fmt($totalCompromisos) . '</span>'
                                            . '</div>';

                                        if ($servicioDeudasMes <= 0) {
                                            $html .= '<p style="font-size:0.75rem;color:' . $cGreen . ';padding:0.5rem 0;">'
                                                . '✓ Sin cuotas de deuda registradas este mes. Los costos fijos ($ ' . $fmt($totalFijosMensual) . '/mes) ya están cubiertos en el análisis anterior.'
                                                . '</p>';
                                        } else {
                                            // Cards: 3 columnas
                                            $html .= '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:1.25rem;">';

                                            $html .= '<div style="padding:0.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;">'
                                                . '<p style="font-size:0.63rem;font-weight:700;color:#64748b;text-transform:uppercase;margin:0 0 0.3rem;">Costos Fijos Op.</p>'
                                                . '<p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">$ ' . $fmt($totalFijosMensual) . '</p>'
                                                . '<p style="font-size:0.63rem;color:#94a3b8;margin:0.2rem 0 0;">' . number_format($totalFijosMensual / $totalCompromisos * 100, 1) . '% del total</p>'
                                                . '</div>';

                                            $html .= '<div style="padding:0.75rem 1rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.75rem;">'
                                                . '<p style="font-size:0.63rem;font-weight:700;color:#1d4ed8;text-transform:uppercase;margin:0 0 0.3rem;">Cuotas Deuda (mes)</p>'
                                                . '<p style="font-size:1rem;font-weight:700;color:#1d4ed8;margin:0;">$ ' . $fmt($servicioDeudasMes) . '</p>'
                                                . '<p style="font-size:0.63rem;color:#93c5fd;margin:0.2rem 0 0;">' . number_format($servicioDeudasMes / $totalCompromisos * 100, 1) . '% del total</p>'
                                                . '</div>';

                                            $html .= '<div style="padding:0.75rem 1rem;background:#4c1d95;border-radius:0.75rem;">'
                                                . '<p style="font-size:0.63rem;font-weight:700;color:#c4b5fd;text-transform:uppercase;margin:0 0 0.3rem;">Total Compromisos</p>'
                                                . '<p style="font-size:1rem;font-weight:700;color:#fff;margin:0;">$ ' . $fmt($totalCompromisos) . '</p>'
                                                . '<p style="font-size:0.63rem;color:#a78bfa;margin:0.2rem 0 0;">operativo + servicio deuda</p>'
                                                . '</div>';

                                            $html .= '</div>';

                                            // Fila: PE total + Aporte de esta producción
                                            $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.25rem;">';

                                            $html .= '<div style="padding:0.75rem 1rem;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:0.75rem;">'
                                                . '<p style="font-size:0.63rem;font-weight:700;color:#7c3aed;text-transform:uppercase;margin:0 0 0.3rem;">PE con todos los compromisos</p>'
                                                . '<p style="font-size:1rem;font-weight:700;color:#6d28d9;margin:0;">' . ($peTotalUnidades !== null ? number_format($peTotalUnidades, 0) . ' u.' : '—') . '</p>'
                                                . '<p style="font-size:0.63rem;color:#8b5cf6;margin:0.2rem 0 0;">' . ($peTotalMonetario !== null ? '= $ ' . $fmt($peTotalMonetario) . ' en ventas' : '') . '</p>'
                                                . '</div>';

                                            $html .= '<div style="padding:0.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;">'
                                                . '<p style="font-size:0.63rem;font-weight:700;color:#64748b;text-transform:uppercase;margin:0 0 0.3rem;">Aporta esta producción</p>'
                                                . '<p style="font-size:1rem;font-weight:700;color:' . $colorAporte . ';margin:0;">$ ' . $fmt($contribucionTotal) . '</p>'
                                                . '<p style="font-size:0.63rem;color:#94a3b8;margin:0.2rem 0 0;">' . number_format($pctAporte, 1) . '% de los compromisos</p>'
                                                . '</div>';

                                            $html .= '</div>';

                                            // Barra de cobertura total
                                            if ($coberturaTotal !== null) {
                                                $barPctT   = min($coberturaTotal, 100);
                                                $barColorT = $coberturaTotal >= 100 ? '#16a34a' : ($coberturaTotal >= 50 ? '#f59e0b' : '#ef4444');

                                                $html .= '<div style="margin-bottom:0.5rem;">';
                                                $html .= '<div style="display:flex;justify-content:space-between;font-size:0.68rem;color:#6b7280;margin-bottom:0.3rem;">'
                                                    . '<span>0%</span><span>Equilibrio con todos los compromisos (100%)</span>'
                                                    . '</div>';
                                                $html .= '<div style="position:relative;height:1.5rem;background:#f1f5f9;border-radius:999px;overflow:hidden;">';
                                                $html .= '<div style="height:100%;width:' . number_format($barPctT, 1) . '%;background:' . $barColorT . ';border-radius:999px;display:flex;align-items:center;justify-content:center;">';
                                                if ($barPctT > 15) {
                                                    $html .= '<span style="font-size:0.6rem;font-weight:700;color:#fff;">' . number_format($coberturaTotal, 1) . '%</span>';
                                                }
                                                $html .= '</div></div>';

                                                if ($coberturaTotal >= 100) {
                                                    $html .= '<p style="margin-top:0.5rem;font-size:0.75rem;color:' . $cGreen . ';font-weight:600;">✓ Esta producción cubre todos los compromisos financieros (operativos + deudas) y genera excedente.</p>';
                                                } else {
                                                    $faltanU = max($peTotalUnidades - $cantidad, 0);
                                                    $html .= '<p style="margin-top:0.5rem;font-size:0.75rem;color:' . $cAmb . ';">⚠ Faltan <strong>' . number_format($faltanU, 0) . ' unidades</strong> adicionales para cubrir la totalidad de los compromisos financieros.</p>';
                                                }
                                                $html .= '</div>';
                                            }
                                        }

                                        $html .= '</div>'; // close compromisos section
                                    }

                                    return new \Illuminate\Support\HtmlString($html);
                                })
                                ->columnSpanFull(),

                                // ── Acciones PE ──────────────────────────────────────
                                \Filament\Forms\Components\Actions::make([

                                    // ── Modal resumen rápido ──────────────────────────
                                    \Filament\Forms\Components\Actions\Action::make('ver_pe_resumen')
                                        ->label('📊 Ver Resumen')
                                        ->color('info')
                                        ->icon('heroicon-o-chart-bar')
                                        ->modalHeading('Punto de Equilibrio — Resumen')
                                        ->modalWidth('2xl')
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Cerrar')
                                        ->mountUsing(function (\Filament\Forms\Form $form, callable $get) {
                                            $empresa  = \Filament\Facades\Filament::getTenant();
                                            $presKey  = $get('_plan_presentation_id');
                                            $cantidad = (float) ($get('_plan_cantidad') ?? 0);
                                            $pres     = ($get('presentations') ?? [])[$presKey] ?? null;

                                            if (! $presKey || $cantidad <= 0 || ! $pres) {
                                                $form->fill(['_pe_modal_html' => '<p style="color:#6b7280;text-align:center;padding:2rem 1rem;">Configura la producción en la pestaña <strong>Simulación y Análisis</strong> para ver el resumen.</p>']);
                                                return;
                                            }

                                            $fmt  = fn ($v) => '$ ' . number_format((float) $v, 2);
                                            $pct  = fn ($v) => number_format((float) $v, 1) . '%';
                                            $card = fn ($lbl, $val, $clr = '#1e40af', $bg = '#eff6ff') =>
                                                '<div style="padding:0.85rem;background:' . $bg . ';border-radius:0.5rem;text-align:center;">'
                                                . '<p style="margin:0;font-size:0.65rem;color:#6b7280;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">' . $lbl . '</p>'
                                                . '<p style="margin:0.2rem 0 0;font-size:1.15rem;font-weight:800;color:' . $clr . ';">' . $val . '</p>'
                                                . '</div>';

                                            // PVP
                                            $pvpCampo   = (float) ($get('_plan_pvp_venta') ?? 0);
                                            $incluyeIva = (bool)  ($get('_plan_pvp_incluye_iva') ?? false);
                                            $pvpSinIva  = ($pvpCampo > 0 && $incluyeIva) ? round($pvpCampo / 1.15, 4) : $pvpCampo;
                                            $capacidad  = (float) ($get('capacidad_instalada_mensual') ?? 0);
                                            $fracMes    = $capacidad > 0 ? $cantidad / $capacidad : 0;

                                            // Materiales
                                            $lote     = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                            $factor   = $cantidad / $lote;
                                            $totalMat = 0;
                                            foreach ($pres['formulaLines'] ?? [] as $line) {
                                                $item       = ($line['inventory_item_id'] ?? null) ? \App\Models\InventoryItem::find($line['inventory_item_id']) : null;
                                                $cantBase   = (float) ($line['cantidad'] ?? 0);
                                                $factorConv = max((float) ($item?->conversion_factor ?? 1), 0.000001);
                                                $puId       = $item?->purchase_unit_id ?? null;
                                                $fUnitId    = $line['measurement_unit_id'] ?? null;
                                                $sUnitId    = $item?->measurement_unit_id;
                                                $cantNec    = round($cantBase * $factor, 6);
                                                if ($fUnitId == $sUnitId || ! $fUnitId) $cantNecS = $cantNec;
                                                elseif ($puId && $fUnitId == $puId && $puId != $sUnitId) $cantNecS = round($cantNec * $factorConv, 6);
                                                else $cantNecS = round($cantNec / $factorConv, 6);
                                                [$cPorU] = self::costoLinea($item, 1, $fUnitId);
                                                $totalMat += $cPorU * $cantNecS;
                                            }

                                            // MO + Indirectos
                                            $personas      = (float) ($get('num_personas') ?? 0);
                                            $costoMo       = (float) ($get('costo_mano_obra_persona') ?? 0);
                                            $totalMO       = $personas * $costoMo * $fracMes;
                                            $totalOtrosInd = 0;
                                            foreach ($get('indirectCosts') ?? [] as $ind) {
                                                $m = (float) ($ind['monto_mensual'] ?? 0);
                                                $totalOtrosInd += match ($ind['frecuencia'] ?? 'mensual') {
                                                    'semanal' => $m * 4.33 * $fracMes,
                                                    'unico'   => $m,
                                                    default   => $m * $fracMes,
                                                };
                                            }

                                            // Costos fijos
                                            $costosFijos       = \App\Models\CostoFijo::where('empresa_id', $empresa->id)->where('activo', true)->get();
                                            $totalFijosMensual = $costosFijos->sum(fn ($c) => $c->monto_mensual);
                                            $totalFijosProrr   = $totalFijosMensual * $fracMes;

                                            $costoVariable = $totalMat + $totalMO + $totalOtrosInd;
                                            $costoTotal    = $costoVariable + $totalFijosProrr;
                                            $costoUnitario = $cantidad > 0 ? $costoTotal / $cantidad : 0;
                                            $costoVarUnit  = $cantidad > 0 ? $costoVariable / $cantidad : 0;

                                            if ($pvpSinIva <= 0) {
                                                $margenPct = (float) ($get('_plan_margen_venta') ?? 30);
                                                $div       = 1 - ($margenPct / 100);
                                                $pvpSinIva = ($div > 0 && $costoUnitario > 0) ? round($costoUnitario / $div, 2) : 0;
                                            }

                                            // PE operativo
                                            $contribucionUnit = $pvpSinIva - $costoVarUnit;
                                            $peUnidades       = $contribucionUnit > 0 ? (int) ceil($totalFijosMensual / $contribucionUnit) : null;
                                            $peMonetario      = $peUnidades !== null ? $peUnidades * $pvpSinIva : null;
                                            $coberturaOp      = ($peUnidades !== null && $peUnidades > 0) ? min(round($cantidad / $peUnidades * 100, 1), 999) : null;

                                            // Deudas del mes
                                            $servicioDeudasMes = (float) \App\Models\DebtAmortizationLine::whereHas(
                                                'debt', fn ($q) => $q->where('empresa_id', $empresa->id)
                                            )->whereMonth('fecha_vencimiento', now()->month)
                                             ->whereYear('fecha_vencimiento', now()->year)
                                             ->where('estado', '!=', 'pagada')
                                             ->sum('total_cuota');

                                            $totalCompromisos  = $totalFijosMensual + $servicioDeudasMes;
                                            $contribucionTotal  = $contribucionUnit * $cantidad;
                                            $peTotalUnidades   = ($contribucionUnit > 0 && $totalCompromisos > 0) ? (int) ceil($totalCompromisos / $contribucionUnit) : null;
                                            $peTotalMonetario  = $peTotalUnidades !== null ? $peTotalUnidades * $pvpSinIva : null;
                                            $coberturaTotal    = ($peTotalUnidades !== null && $peTotalUnidades > 0) ? min(round($cantidad / $peTotalUnidades * 100, 1), 999) : null;
                                            $pctAporte         = $totalCompromisos > 0 ? min(round($contribucionTotal / $totalCompromisos * 100, 1), 999) : 0;

                                            // ── Construir HTML del modal ──────────────────
                                            $html = '<div style="font-family:sans-serif;font-size:13px;">';

                                            // Fila de métricas clave
                                            $html .= '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.6rem;margin-bottom:0.85rem;">';
                                            $contribClr = $contribucionUnit > 0 ? '#15803d' : '#dc2626';
                                            $contribBg  = $contribucionUnit > 0 ? '#f0fdf4' : '#fef2f2';
                                            $html .= $card('Contribución Unitaria', $fmt($contribucionUnit), $contribClr, $contribBg);
                                            $html .= $card('Costo Variable Unitario', $fmt($costoVarUnit), '#92400e', '#fffbeb');
                                            $html .= $card('PVP sin IVA', $fmt($pvpSinIva), '#1e40af', '#eff6ff');
                                            $html .= $card('Costo Unitario Total', $fmt($costoUnitario), '#374151', '#f9fafb');
                                            $html .= '</div>';

                                            // PE Operativo
                                            $html .= '<div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.85rem;margin-bottom:0.75rem;">';
                                            $html .= '<p style="margin:0 0 0.5rem;font-size:0.65rem;font-weight:700;text-transform:uppercase;color:#475569;letter-spacing:0.08em;">PE Operativo — Costos Fijos: ' . $fmt($totalFijosMensual) . '/mes</p>';
                                            $html .= '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;">';
                                            $html .= $card('Unidades PE', $peUnidades !== null ? number_format($peUnidades) . ' u.' : '—', '#1e40af', '#fff');
                                            $html .= $card('Monto PE', $peMonetario !== null ? $fmt($peMonetario) : '—', '#1e40af', '#fff');
                                            $covClr = $coberturaOp === null ? '#6b7280' : ($coberturaOp >= 100 ? '#15803d' : ($coberturaOp >= 50 ? '#92400e' : '#dc2626'));
                                            $html .= $card('Cobertura', $coberturaOp !== null ? $pct($coberturaOp) : '—', $covClr, '#fff');
                                            $html .= '</div>';
                                            if ($coberturaOp !== null) {
                                                $barPct   = min($coberturaOp, 100);
                                                $barColor = $coberturaOp >= 100 ? '#22c55e' : ($coberturaOp >= 50 ? '#f59e0b' : '#ef4444');
                                                $html .= '<div style="margin-top:0.6rem;background:#e5e7eb;border-radius:999px;height:7px;">'
                                                    . '<div style="width:' . $barPct . '%;background:' . $barColor . ';height:7px;border-radius:999px;"></div></div>';
                                                $html .= '<p style="margin:0.3rem 0 0;font-size:0.62rem;color:#6b7280;">Esta producción de <strong>' . number_format($cantidad, 0) . ' u.</strong> cubre el ' . $pct($coberturaOp) . ' del PE operativo.</p>';
                                            }
                                            $html .= '</div>';

                                            // PE Total (si hay deudas)
                                            if ($servicioDeudasMes > 0) {
                                                $html .= '<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:0.5rem;padding:0.85rem;margin-bottom:0.75rem;">';
                                                $html .= '<p style="margin:0 0 0.5rem;font-size:0.65rem;font-weight:700;text-transform:uppercase;color:#92400e;letter-spacing:0.08em;">PE Total — Fijos + Deudas: ' . $fmt($totalCompromisos) . '/mes</p>';
                                                $html .= '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;">';
                                                $html .= $card('Unidades PE', $peTotalUnidades !== null ? number_format($peTotalUnidades) . ' u.' : '—', '#92400e', '#fff');
                                                $html .= $card('Monto PE', $peTotalMonetario !== null ? $fmt($peTotalMonetario) : '—', '#92400e', '#fff');
                                                $covTClr = $coberturaTotal === null ? '#6b7280' : ($coberturaTotal >= 100 ? '#15803d' : ($coberturaTotal >= 50 ? '#92400e' : '#dc2626'));
                                                $html .= $card('Cobertura', $coberturaTotal !== null ? $pct($coberturaTotal) : '—', $covTClr, '#fff');
                                                $html .= '</div>';
                                                $html .= '<p style="margin:0.4rem 0 0;font-size:0.62rem;color:#92400e;">Esta producción aporta el <strong>' . $pct($pctAporte) . '</strong> del total de compromisos del mes.</p>';
                                                $html .= '</div>';
                                            }

                                            $presNombre = $pres['nombre'] ?? '—';
                                            $html .= '<p style="margin:0;font-size:0.6rem;color:#9ca3af;text-align:center;">Presentación: <strong>' . e($presNombre) . '</strong> · ' . number_format($cantidad, 0) . ' u. · Datos en tiempo real del formulario.</p>';
                                            $html .= '</div>';

                                            $form->fill(['_pe_modal_html' => $html]);
                                        })
                                        ->form([
                                            \Filament\Forms\Components\Placeholder::make('_pe_modal_html')
                                                ->label('')
                                                ->content(fn (\Filament\Forms\Get $get) => new \Illuminate\Support\HtmlString($get('_pe_modal_html') ?? ''))
                                                ->columnSpanFull(),
                                        ]),

                                    // ── Descargar PDF (ruta web, fuera de Livewire) ───
                                    \Filament\Forms\Components\Actions\Action::make('descargar_pe_pdf')
                                        ->label('📄 Descargar PDF')
                                        ->color('gray')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->tooltip(fn ($record) => \App\Models\ProductSimulation::where('product_design_id', $record?->id)->exists()
                                            ? 'Descargar informe PDF del Punto de Equilibrio'
                                            : 'Guarda una simulación primero para habilitar la descarga')
                                        ->url(function ($record) {
                                            if (! $record) return null;
                                            $empresa    = \Filament\Facades\Filament::getTenant();
                                            $simulation = \App\Models\ProductSimulation::where('empresa_id', $empresa->id)
                                                ->where('product_design_id', $record->id)
                                                ->latest()->first();
                                            if (! $simulation) return null;
                                            return route('product-design.equilibrio.print', [
                                                'empresa'    => $empresa->slug,
                                                'design'     => $record->id,
                                                'simulation' => $simulation->id,
                                                'download'   => 1,
                                            ]);
                                        })
                                        ->openUrlInNewTab(),

                                ])->columnSpanFull(),
                        ]),

                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->searchable(),
                TextColumn::make('presentations_count')
                    ->counts('presentations')
                    ->label('Presentaciones')
                    ->badge()
                    ->color('info'),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductDesigns::route('/'),
            'create' => Pages\CreateProductDesign::route('/create'),
            'edit'   => Pages\EditProductDesign::route('/{record}/edit'),
        ];
    }

    /**
     * Calcula el costo de una línea de fórmula usando $get() del repeater.
     * Lee precio y factor directo del ítem en BD para evitar depender de campos ocultos.
     */
    public static function calcularCostoLinea(callable $get, callable $set): void
    {
        $qty    = (float) ($get('cantidad') ?? 0);
        $itemId = $get('inventory_item_id');
        $unitId = $get('measurement_unit_id');
        $item   = $itemId ? InventoryItem::find($itemId) : null;

        [$costo] = self::costoLinea($item, $qty, $unitId);
        $set('costo_estimado', round($costo, 4));

        // Actualizar campos ocultos de display (para que los placeholders reactivos funcionen)
        if ($item) {
            $set('_precio_compra', (float) $item->purchase_price);
            $set('_factor', max((float) ($item->conversion_factor ?? 1), 0.000001));
        }
    }

    /**
     * Obtiene el total mensual de costos fijos activos de la empresa actual.
     * Usado en planCostoUnitario(), liquidación y calcEscenario().
     */
    public static function costosFijosMensuales(): float
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant) return 0;

        return \App\Models\CostoFijo::where('empresa_id', $tenant->id)
            ->where('activo', true)
            ->get()
            ->sum(fn ($cf) => $cf->monto_mensual);
    }

    /**
     * Renderiza la tabla HTML de costos fijos de empresa agrupada por categoría.
     */
    /**
     * Renderiza la lista compacta de simulaciones activas para el panel de capacidad.
     */
    public static function renderListaSimulaciones($sims): string
    {
        $html = '<div style="margin-top:0.75rem;">';
        $html .= '<p style="font-size:0.68rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem;">Simulaciones en proyecto:</p>';
        $html .= '<div style="display:flex;flex-wrap:wrap;gap:0.5rem;">';
        foreach ($sims as $sim) {
            $html .= '<span style="padding:0.25rem 0.6rem;border-radius:999px;font-size:0.68rem;background:#f1f5f9;border:1px solid #e2e8f0;color:#374151;">'
                . '<strong>' . e($sim->nombre) . '</strong>'
                . ' · ' . e($sim->presentation_nombre)
                . ' · ' . number_format($sim->cantidad, 0) . ' u.'
                . '</span>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public static function renderTablaCostosFijos($costosFijos, float $totalMensual): string
    {
        $fmt = fn ($v) => number_format((float) $v, 2);

        $html = '<div style="margin-top:1.5rem;">';
        $html .= '<div style="margin-bottom:0.75rem;padding:0.6rem 1rem;background:#f5f3ff;border-radius:0.5rem;border:1px solid #c4b5fd;">'
            . '<span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#4c1d95;">🏢 COSTOS FIJOS DE LA EMPRESA</span>'
            . '<span style="float:right;font-size:0.7rem;color:#6d28d9;">Total mensual: $ ' . $fmt($totalMensual) . '</span>'
            . '</div>';

        if ($costosFijos->isEmpty()) {
            $html .= '<div style="padding:1.5rem;border:2px dashed #c4b5fd;border-radius:0.75rem;text-align:center;color:#7c3aed;">'
                . '<p style="font-size:0.85rem;margin-bottom:0.4rem;">Sin costos fijos registrados</p>'
                . '<p style="font-size:0.75rem;color:#a78bfa;">Ingresa los costos fijos de tu empresa en <strong>Planificación → Costos Fijos</strong> para un análisis más preciso.</p>'
                . '</div>';
            $html .= '</div>';
            return $html;
        }

        $grouped = $costosFijos->groupBy('categoria');

        $html .= '<table style="width:100%;border-collapse:collapse;font-size:0.78rem;">';
        $thStyle = 'style="padding:0.4rem 0.75rem;background:#f8fafc;border-bottom:2px solid #e5e7eb;text-align:left;font-size:0.68rem;color:#475569;text-transform:uppercase;letter-spacing:0.05em;"';
        $html .= '<thead><tr>'
            . '<th ' . $thStyle . '>Concepto</th>'
            . '<th ' . $thStyle . '>Categoría</th>'
            . '<th ' . $thStyle . ' style="text-align:right;">Monto</th>'
            . '<th ' . $thStyle . ' style="text-align:center;">Frecuencia</th>'
            . '<th ' . $thStyle . ' style="text-align:right;">Equiv. Mensual</th>'
            . '</tr></thead><tbody>';

        $catColors = [
            'Instalaciones' => '#3b82f6', 'Servicios' => '#06b6d4', 'Personal' => '#f97316',
            'Tecnología' => '#8b5cf6', 'Marketing' => '#ec4899', 'Financiero' => '#eab308', 'Operativo' => '#6b7280',
        ];

        foreach ($grouped as $cat => $items) {
            $catColor = $catColors[$cat] ?? '#6b7280';
            $subtotal = $items->sum(fn ($cf) => $cf->monto_mensual);
            foreach ($items as $i => $cf) {
                $html .= '<tr style="border-bottom:1px solid #f1f5f9;">'
                    . '<td style="padding:0.4rem 0.75rem;">' . e($cf->nombre) . '</td>'
                    . '<td style="padding:0.4rem 0.75rem;"><span style="padding:0.1rem 0.5rem;border-radius:999px;font-size:0.65rem;font-weight:600;background:' . $catColor . '1a;color:' . $catColor . ';">' . e($cat) . '</span></td>'
                    . '<td style="padding:0.4rem 0.75rem;text-align:right;font-family:monospace;">$ ' . $fmt($cf->monto) . '</td>'
                    . '<td style="padding:0.4rem 0.75rem;text-align:center;font-size:0.7rem;color:#6b7280;">' . ucfirst($cf->frecuencia) . '</td>'
                    . '<td style="padding:0.4rem 0.75rem;text-align:right;font-family:monospace;font-weight:600;color:#4c1d95;">$ ' . $fmt($cf->monto_mensual) . '</td>'
                    . '</tr>';
            }
            // Subtotal por categoría
            $html .= '<tr style="background:#f8fafc;border-bottom:2px solid #e5e7eb;">'
                . '<td colspan="4" style="padding:0.3rem 0.75rem;font-size:0.7rem;font-weight:600;color:' . $catColor . ';text-align:right;">Subtotal ' . e($cat) . '</td>'
                . '<td style="padding:0.3rem 0.75rem;text-align:right;font-family:monospace;font-weight:700;color:' . $catColor . ';">$ ' . $fmt($subtotal) . '</td>'
                . '</tr>';
        }

        // Total general
        $html .= '<tr style="background:#1e293b;">'
            . '<td colspan="4" style="padding:0.5rem 0.75rem;font-weight:700;color:#fff;">TOTAL MENSUAL</td>'
            . '<td style="padding:0.5rem 0.75rem;text-align:right;font-family:monospace;font-weight:700;color:#fbbf24;">$ ' . $fmt($totalMensual) . '</td>'
            . '</tr>';
        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Calcula el costo de una línea según la unidad usada:
     * - Si la unidad de la línea es la unidad de STOCK (g):  costo = (precio_compra ÷ factor) × qty
     * - Si la unidad de la línea es la unidad de COMPRA (kg): costo = precio_compra × qty
     * - Sin conversión configurada: costo = precio_compra × qty
     *
     * Retorna [costo_float, detalle_string]
     */
    public static function costoLinea(?InventoryItem $item, float $qty, ?int $unitId): array
    {
        if (!$item || $qty <= 0) return [0.0, '—'];

        $precioCompra = (float) $item->purchase_price;
        $factor       = max((float) ($item->conversion_factor ?? 1), 0.000001);
        $puLabel      = $item->purchaseUnit?->abreviatura ?? $item->measurementUnit?->abreviatura ?? '';
        $suLabel      = $item->measurementUnit?->abreviatura ?? '';

        // Si la unidad de la fórmula es la unidad de COMPRA → precio directo (no dividir)
        if ($item->purchase_unit_id && $unitId == $item->purchase_unit_id) {
            $costo   = $precioCompra * $qty;
            $detalle = "\${$precioCompra} × {$qty} {$puLabel}";
        } else {
            // Unidad de stock u otra → aplicar conversión: precio_compra ÷ factor × qty
            $costo   = ($precioCompra / $factor) * $qty;
            $detalle = "\${$precioCompra} ÷ {$factor} × {$qty} {$suLabel}";
        }

        return [round($costo, 6), $detalle];
    }

    /**
     * Suma el costo total de todas las líneas de una fórmula.
     */
    public static function costoLote(array $lines): float
    {
        $total = 0.0;
        foreach ($lines as $line) {
            $itemId = $line['inventory_item_id'] ?? null;
            $item   = $itemId ? InventoryItem::find($itemId) : null;
            $qty    = (float) ($line['cantidad'] ?? 0);
            $unitId = $line['measurement_unit_id'] ?? null;
            [$costo] = self::costoLinea($item, $qty, $unitId);
            $total += $costo;
        }
        return $total;
    }

    /**
     * Calcula el costo unitario completo (materiales + indirectos) para el plan de producción.
     * Usado por los campos reactivos PVP ↔ Margen.
     */
    public static function planCostoUnitario(callable $get): float
    {
        $presKey   = $get('_plan_presentation_id');
        $cantidad  = (float) ($get('_plan_cantidad') ?? 0);
        $capacidad = (float) ($get('capacidad_instalada_mensual') ?? 0);

        if (!$presKey || $cantidad <= 0) return 0;

        $pres = ($get('presentations') ?? [])[$presKey] ?? null;
        if (!$pres) return 0;

        $lote   = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
        $factor = $cantidad / $lote;

        // Costo total de materiales (incluye lo que hay en stock)
        $totalMat = 0;
        foreach ($pres['formulaLines'] ?? [] as $line) {
            $item          = ($line['inventory_item_id'] ?? null) ? InventoryItem::find($line['inventory_item_id']) : null;
            $cantBase      = (float) ($line['cantidad'] ?? 0);
            $factorConvT   = max((float) ($item?->conversion_factor ?? 1), 0.000001);
            $puIdT         = $item?->purchase_unit_id ?? null;
            $fUnitId       = $line['measurement_unit_id'] ?? null;
            $stockUnitIdT  = $item?->measurement_unit_id;
            $cantNecFormula = round($cantBase * $factor, 6);
            if ($fUnitId == $stockUnitIdT || !$fUnitId) {
                $cantNecStock = $cantNecFormula;
            } elseif ($puIdT && $fUnitId == $puIdT && $puIdT != $stockUnitIdT) {
                $cantNecStock = round($cantNecFormula * $factorConvT, 6);
            } else {
                $cantNecStock = round($cantNecFormula / $factorConvT, 6);
            }
            [$costoPorU] = self::costoLinea($item, 1, $fUnitId);
            $totalMat += $costoPorU * $cantNecStock;
        }

        // Indirectos prorrateados
        $fracMes     = $capacidad > 0 ? $cantidad / $capacidad : 0;
        $personasSim = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));
        $costoMoSim  = (float) ($get('_plan_costo_mo_persona') ?: ($get('costo_mano_obra_persona') ?? 0));
        $totalInd    = $personasSim * $costoMoSim * $fracMes;
        foreach ($get('indirectCosts') ?? [] as $indItem) {
            $monto = (float) ($indItem['monto_mensual'] ?? 0);
            $totalInd += match ($indItem['frecuencia'] ?? 'mensual') {
                'semanal' => $monto * 4.33 * $fracMes,
                'unico'   => $monto,
                default   => $monto * $fracMes,
            };
        }

        // Costos fijos de empresa prorrateados
        $totalFijosEmpresa = self::costosFijosMensuales() * $fracMes;

        return $cantidad > 0 ? ($totalMat + $totalInd + $totalFijosEmpresa) / $cantidad : 0;
    }

    /**
     * Extrae los argumentos comunes para los cálculos de escenarios desde el formulario.
     * Devuelve [$pres, $lote, $capacidad, $pvpSinIva, $pvpConIva, $margenPct, $icePct, $personas, $costoMo, $indCosts]
     */
    public static function escArgs(array $pres, callable $get): array
    {
        $lote       = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
        $capacidad  = (float) ($get('capacidad_instalada_mensual') ?? 0);
        $pvpCampo   = (float) ($get('_plan_pvp_venta') ?? 0);
        $incluyeIva = (bool)  ($get('_plan_pvp_incluye_iva') ?? false);
        $pvpSinIva  = ($pvpCampo > 0 && $incluyeIva) ? $pvpCampo / 1.15 : $pvpCampo;
        $pvpConIva  = $incluyeIva ? $pvpCampo : round($pvpSinIva * 1.15, 4);
        $margenPct  = (float) ($get('_plan_margen_venta') ?? 0);
        $icePct     = (bool)  ($get('_plan_aplica_ice') ?? false)
            ? (float) ($get('_plan_ice_porcentaje') ?? 0) / 100 : 0;
        $personas   = (float) ($get('_plan_num_personas') ?: ($get('num_personas') ?? 0));
        $costoMo    = (float) ($get('_plan_costo_mo_persona') ?: ($get('costo_mano_obra_persona') ?? 0));
        $indCosts   = $get('indirectCosts') ?? [];
        return [$pres, $lote, $capacidad, $pvpSinIva, $pvpConIva, $margenPct, $icePct, $personas, $costoMo, $indCosts];
    }

    /**
     * Renderiza la tabla HTML de sensibilidad por escenarios.
     * $isPdf = true aplica estilos compatibles con dompdf (sin flex/grid).
     */
    public static function renderTablaEscenarios(array $escenarios, ?float $peQty, float $cantidad, bool $isPdf = false): string
    {
        $fmt = fn ($v) => number_format((float) $v, 2);

        $mejorROI = collect($escenarios)->filter(fn ($e) => $e['utilNeta'] > 0)->sortByDesc('roi')->first();

        // Header
        if ($isPdf) {
            $html  = '<h3 style="margin:1.5rem 0 0.4rem;padding:0.35rem 0.75rem;background:#1e293b1a;border-left:3px solid #1e293b;font-size:0.82rem;color:#1e293b;font-weight:700;">📊 Análisis de Viabilidad por Cantidad</h3>';
            if ($peQty !== null)
                $html .= '<p style="font-size:0.72rem;color:#92400e;margin:0 0 0.4rem;">⚖️ Punto de equilibrio: <strong>' . number_format($peQty, 0) . ' u.</strong></p>';
            else
                $html .= '<p style="font-size:0.72rem;color:#15803d;margin:0 0 0.4rem;">✓ Todos los escenarios simulados son rentables.</p>';
        } else {
            $html  = '<div style="font-family:sans-serif;">';
            $html .= '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem;">';
            $html .= '<div><span style="font-size:0.82rem;font-weight:700;color:#1e293b;">📊 Análisis de Viabilidad por Cantidad</span>'
                . '<span style="margin-left:0.75rem;font-size:0.72rem;color:#6b7280;">¿Qué tan viable es producir más o menos?</span></div>';
            if ($peQty !== null)
                $html .= '<span style="padding:0.2rem 0.75rem;border-radius:999px;font-size:0.72rem;font-weight:700;background:#fef3c7;border:1px solid #fde68a;color:#92400e;">⚖️ Punto de equilibrio: ' . number_format($peQty, 0) . ' u.</span>';
            else
                $html .= '<span style="padding:0.2rem 0.75rem;border-radius:999px;font-size:0.72rem;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">✓ Todos los escenarios son rentables</span>';
            $html .= '</div>';
        }

        if ($isPdf) {
            // ── PDF: dos tablas compactas (6 cols c/u) para caber en A4 portrait ──
            $th  = fn ($t, $a = 'right') => '<th style="padding:0.3rem 0.4rem;background:#f1f5f9;border-bottom:2px solid #e2e8f0;border:1px solid #e2e8f0;font-size:0.65rem;color:#475569;text-align:' . $a . ';">' . $t . '</th>';
            $td  = fn ($v, $c = '#374151', $b = false) => '<td style="padding:0.28rem 0.4rem;border:1px solid #f1f5f9;text-align:right;font-size:0.7rem;color:' . $c . ';' . ($b ? 'font-weight:700;' : '') . '">' . $v . '</td>';
            $tdL = fn ($v, $c = '#374151', $b = false) => '<td style="padding:0.28rem 0.4rem;border:1px solid #f1f5f9;text-align:left;font-size:0.7rem;color:' . $c . ';' . ($b ? 'font-weight:700;' : '') . '">' . $v . '</td>';

            // Tabla 1: Costos
            $html .= '<p style="font-size:0.68rem;font-weight:700;color:#475569;margin:0.4rem 0 0.2rem;">Estructura de Costos por Escenario</p>';
            $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:0.6rem;">';
            $html .= '<thead><tr>' . $th('Cantidad', 'left') . $th('Stock disp.') . $th('A comprar') . $th('MO + Ind.') . $th('Costo total') . $th('Costo/u.') . '</tr></thead><tbody>';
            foreach ($escenarios as $esc) {
                $esCant    = $esc['cantidad'];
                $esCurrent = (int) $esCant === (int) $cantidad;
                $rowBg     = $esCurrent ? 'background:#eff6ff;' : '';
                $cantLabel = number_format($esCant, 0) . ' u.' . ($esCurrent ? ' *' : '');
                $html .= '<tr style="' . $rowBg . '">';
                $html .= $tdL('<strong>' . $cantLabel . '</strong>', $esCurrent ? '#1e40af' : '#374151');
                $html .= $td('$ ' . $fmt($esc['matStock']),   $esc['matStock']   > 0 ? '#16a34a' : '#9ca3af');
                $html .= $td('$ ' . $fmt($esc['matComprar']), $esc['matComprar'] > 0 ? '#dc2626' : '#9ca3af');
                $html .= $td('$ ' . $fmt($esc['totalMO'] + $esc['totalOtros']), '#6b7280');
                $html .= $td('$ ' . $fmt($esc['costoTotal']), '#374151', true);
                $html .= $td('$ ' . $fmt($esc['costoU']),     $esCurrent ? '#1e40af' : '#374151', $esCurrent);
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';

            // Tabla 2: Rentabilidad
            $html .= '<p style="font-size:0.68rem;font-weight:700;color:#475569;margin:0 0 0.2rem;">Rentabilidad por Escenario</p>';
            $html .= '<table style="width:100%;border-collapse:collapse;">';
            $html .= '<thead><tr>' . $th('Cantidad', 'left') . $th('Ing. neto') . $th('Util. bruta') . $th('Util. neta') . $th('Margen') . $th('ROI') . $th('', 'center') . '</tr></thead><tbody>';
            foreach ($escenarios as $esc) {
                $esCant    = $esc['cantidad'];
                $esCurrent = (int) $esCant === (int) $cantidad;
                $rentable  = $esc['utilNeta'] >= 0;
                $esPE      = $peQty !== null && (int) $esCant === (int) $peQty;
                $rowBg     = $esCurrent ? 'background:#eff6ff;' : (!$rentable ? 'background:#fef2f2;' : '');
                $cantLabel = number_format($esCant, 0) . ' u.' . ($esCurrent ? ' *' : '');
                $estado    = $rentable ? '✓' : '✗';
                $estadoClr = $rentable ? '#15803d' : '#dc2626';
                if ($esPE) $estado .= ' PE';
                $html .= '<tr style="' . $rowBg . '">';
                $html .= $tdL('<strong>' . $cantLabel . '</strong>', $esCurrent ? '#1e40af' : '#374151');
                $html .= $td('$ ' . $fmt($esc['ingresoNeto']), '#374151');
                $html .= $td('$ ' . $fmt($esc['utilBruta']),  $esc['utilBruta'] >= 0 ? '#374151' : '#dc2626');
                $html .= $td('$ ' . $fmt($esc['utilNeta']),   $rentable ? '#16a34a' : '#dc2626', true);
                $html .= $td(number_format($esc['margenNeto'], 1) . '%', $rentable ? '#16a34a' : '#dc2626');
                $html .= $td(number_format($esc['roi'], 1) . '%',
                    $esc['roi'] >= 30 ? '#7c3aed' : ($esc['roi'] >= 10 ? '#374151' : '#dc2626'));
                $html .= '<td style="padding:0.28rem 0.4rem;border:1px solid #f1f5f9;text-align:center;font-size:0.7rem;font-weight:700;color:' . $estadoClr . ';">' . $estado . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            // ── Web: tabla única con scroll horizontal ───────────────────────────
            $thStyle = 'padding:0.45rem 0.5rem;background:#f1f5f9;border-bottom:2px solid #e2e8f0;font-size:0.7rem;color:#475569;white-space:nowrap;';
            $html .= '<div style="overflow-x:auto;">';
            $html .= '<table style="width:100%;border-collapse:collapse;font-size:0.75rem;">';
            $html .= '<thead><tr>'
                . '<th style="' . $thStyle . 'text-align:center;">Cantidad</th>'
                . '<th style="' . $thStyle . 'text-align:right;">Stock disponible</th>'
                . '<th style="' . $thStyle . 'text-align:right;">A comprar</th>'
                . '<th style="' . $thStyle . 'text-align:right;">MO + Indirectos</th>'
                . '<th style="' . $thStyle . 'text-align:right;">Costo total</th>'
                . '<th style="' . $thStyle . 'text-align:right;">Costo / u.</th>'
                . '<th style="' . $thStyle . 'text-align:right;">Ingreso neto</th>'
                . '<th style="' . $thStyle . 'text-align:right;">Utilidad neta</th>'
                . '<th style="' . $thStyle . 'text-align:right;">Margen</th>'
                . '<th style="' . $thStyle . 'text-align:right;">ROI</th>'
                . '<th style="' . $thStyle . 'text-align:center;"></th>'
                . '</tr></thead><tbody>';

            foreach ($escenarios as $esc) {
                $esCant    = $esc['cantidad'];
                $esCurrent = (int) $esCant === (int) $cantidad;
                $rentable  = $esc['utilNeta'] >= 0;
                $esPE      = $peQty !== null && (int) $esCant === (int) $peQty;
                $rowBg     = $esCurrent ? '#eff6ff' : (!$rentable ? '#fef2f2' : '#fff');
                $borderL   = $esCurrent ? 'border-left:3px solid #3b82f6;' : 'border-left:3px solid transparent;';

                $td  = fn ($v, $c = '#374151', $b = false) =>
                    '<td style="padding:0.4rem 0.5rem;border-bottom:1px solid #f1f5f9;text-align:right;color:' . $c . ';' . ($b ? 'font-weight:700;' : '') . '">' . $v . '</td>';
                $tdC = fn ($v) =>
                    '<td style="padding:0.4rem 0.5rem;border-bottom:1px solid #f1f5f9;text-align:center;">' . $v . '</td>';

                $cantLabel = number_format($esCant, 0) . ' u.';
                if ($esCurrent) $cantLabel = '<strong>' . $cantLabel . '</strong> <span style="font-size:0.62rem;color:#3b82f6;">← actual</span>';

                $html .= '<tr style="background:' . $rowBg . ';' . $borderL . '">';
                $html .= '<td style="padding:0.4rem 0.5rem;border-bottom:1px solid #f1f5f9;text-align:center;white-space:nowrap;">' . $cantLabel . '</td>';
                $html .= $td('$ ' . $fmt($esc['matStock']),   $esc['matStock']   > 0 ? '#16a34a' : '#9ca3af');
                $html .= $td('$ ' . $fmt($esc['matComprar']), $esc['matComprar'] > 0 ? '#dc2626' : '#9ca3af');
                $html .= $td('$ ' . $fmt($esc['totalMO'] + $esc['totalOtros']), '#6b7280');
                $html .= $td('$ ' . $fmt($esc['costoTotal']), '#374151', true);
                $html .= $td('$ ' . $fmt($esc['costoU']),     $esCurrent ? '#1e40af' : '#374151', $esCurrent);
                $html .= $td('$ ' . $fmt($esc['ingresoNeto']), '#374151');
                $html .= $td('$ ' . $fmt($esc['utilNeta']),   $rentable ? '#16a34a' : '#dc2626', true);
                $html .= $td(number_format($esc['margenNeto'], 1) . '%', $rentable ? '#16a34a' : '#dc2626');
                $html .= $td(number_format($esc['roi'], 1) . '%',
                    $esc['roi'] >= 30 ? '#7c3aed' : ($esc['roi'] >= 10 ? '#374151' : '#dc2626'));

                $estadoBadge = $rentable
                    ? '<span style="padding:0.12rem 0.45rem;border-radius:999px;font-size:0.65rem;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">✓</span>'
                    : '<span style="padding:0.12rem 0.45rem;border-radius:999px;font-size:0.65rem;font-weight:700;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">✗</span>';
                if ($esPE) $estadoBadge .= ' <span style="font-size:0.62rem;color:#d97706;">⚖️</span>';
                $html .= $tdC($estadoBadge);
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Nota pie
        $nota = '';
        if ($mejorROI) {
            $nota = '💡 Mayor ROI en <strong>' . number_format($mejorROI['cantidad'], 0) . ' u.</strong>'
                . ' (' . number_format($mejorROI['roi'], 1) . '% ROI · $ ' . $fmt($mejorROI['costoU']) . '/u.'
                . ' · utilidad neta $ ' . $fmt($mejorROI['utilNeta']) . ').';
            if ($peQty !== null)
                $nota .= ' Rentable a partir de <strong>' . number_format($peQty, 0) . ' u.</strong>';
        }
        if ($isPdf) {
            if ($nota) $html .= '<p style="font-size:0.72rem;color:#6b7280;margin:0.4rem 0 0;">' . $nota . '</p>';
            if ($cantidad > 0) $html .= '<p style="font-size:0.65rem;color:#9ca3af;margin:0.15rem 0 0;">* Fila marcada con asterisco = cantidad ingresada en la simulación.</p>';
        } else {
            if ($nota) $html .= '<p style="margin:0.5rem 0 0;font-size:0.72rem;color:#6b7280;">' . $nota . '</p>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Calcula los indicadores financieros para una cantidad dada.
     * Usado por la tabla de sensibilidad, el modal comparativo y el PDF.
     */
    public static function calcEscenario(
        array   $pres,
        float   $cantEsc,
        float   $lote,
        float   $capacidad,
        float   $pvpSinIva,
        float   $pvpConIva,
        float   $margenPct,
        float   $icePct,
        float   $personas,
        float   $costoMoPer,
        array   $indirectCosts
    ): array {
        $factor = $cantEsc / max($lote, 0.0001);

        $matComprar = 0;
        $matStock   = 0;
        foreach ($pres['formulaLines'] ?? [] as $line) {
            $item        = ($line['inventory_item_id'] ?? null)
                ? InventoryItem::find($line['inventory_item_id']) : null;
            $cantBase    = (float) ($line['cantidad'] ?? 0);
            $factorConv  = max((float) ($item?->conversion_factor ?? 1), 0.000001);
            $puId        = $item?->purchase_unit_id ?? null;
            $fUnitId     = $line['measurement_unit_id'] ?? null;
            $stockUnitId = $item?->measurement_unit_id;
            $cantNecF    = round($cantBase * $factor, 6);
            if ($fUnitId == $stockUnitId || !$fUnitId)
                $cantNecS = $cantNecF;
            elseif ($puId && $fUnitId == $puId && $puId != $stockUnitId)
                $cantNecS = round($cantNecF * $factorConv, 6);
            else
                $cantNecS = round($cantNecF / $factorConv, 6);
            [$cPorU] = self::costoLinea($item, 1, $fUnitId);
            $enStock    = min($cantNecS, max(0, (float) ($item?->stock_actual ?? 0)));
            $matStock   += $cPorU * $enStock;
            $matComprar += $cPorU * max(0, $cantNecS - $enStock);
        }

        $fracMes    = $capacidad > 0 ? $cantEsc / $capacidad : 0;
        $totalMO    = $personas * $costoMoPer * $fracMes;
        $totalOtros = 0;
        foreach ($indirectCosts as $ind) {
            $m = (float) ($ind['monto_mensual'] ?? 0);
            $totalOtros += match ($ind['frecuencia'] ?? 'mensual') {
                'semanal' => $m * 4.33 * $fracMes,
                'unico'   => $m,
                default   => $m * $fracMes,
            };
        }

        // Costos fijos de empresa prorrateados
        $totalFijosEmpresa = self::costosFijosMensuales() * $fracMes;

        $costoTotal  = $matStock + $matComprar + $totalMO + $totalOtros + $totalFijosEmpresa;
        $costoU      = $cantEsc > 0 ? $costoTotal / $cantEsc : 0;
        $inversion   = $matComprar + $totalMO + $totalOtros + $totalFijosEmpresa;

        $pvpBase = $pvpSinIva;
        if ($pvpBase <= 0 && $margenPct > 0) {
            $div = 1 - $margenPct / 100;
            $pvpBase = $div > 0 ? $costoU / $div : 0;
        }
        $pvpPub      = $pvpConIva > 0 ? $pvpConIva : round($pvpBase * 1.15, 4);

        $ingresoBruto = $pvpPub * $cantEsc;
        $ingresoNeto  = $pvpBase * $cantEsc;
        $ivaTotal     = round($ingresoNeto * 0.15, 2);
        $ice          = round($ingresoNeto * $icePct, 2);
        $utilBruta    = $ingresoNeto - $costoTotal;
        $utilNeta     = $utilBruta - $ice;
        $margenNeto   = $ingresoNeto > 0 ? $utilNeta / $ingresoNeto * 100 : 0;
        $roi          = $costoTotal > 0 ? $utilNeta / $costoTotal * 100 : 0;

        return compact(
            'matStock', 'matComprar', 'totalMO', 'totalOtros',
            'costoTotal', 'costoU', 'inversion',
            'ingresoBruto', 'ingresoNeto', 'ivaTotal',
            'utilBruta', 'utilNeta', 'margenNeto', 'roi'
        );
    }

    /**
     * Genera la lista de escenarios de sensibilidad y el punto de equilibrio.
     * Devuelve ['escenarios' => [...], 'peQty' => float|null]
     */
    public static function generarEscenarios(
        array  $pres,
        float  $cantidad,
        float  $lote,
        float  $capacidad,
        float  $pvpSinIva,
        float  $pvpConIva,
        float  $margenPct,
        float  $icePct,
        float  $personas,
        float  $costoMoPer,
        array  $indirectCosts
    ): array {
        $roundLote = fn (float $q) => max($lote, ceil($q / max($lote, 0.0001)) * $lote);
        $targets   = array_unique(array_map('intval', array_map($roundLote, [
            $cantidad * 0.25,
            $cantidad * 0.50,
            $cantidad,
            $cantidad * 2,
            $cantidad * 5,
        ])));
        sort($targets);

        $escenarios = [];
        foreach ($targets as $t) {
            $escenarios[] = ['cantidad' => (float) $t] + self::calcEscenario(
                $pres, (float) $t, $lote, $capacidad,
                $pvpSinIva, $pvpConIva, $margenPct, $icePct,
                $personas, $costoMoPer, $indirectCosts
            );
        }

        // Punto de equilibrio
        $peQty = null;
        if ($escenarios[0]['utilNeta'] < 0) {
            $lo = $lote;
            $hi = (float) end($targets);
            for ($i = 0; $i < 30; $i++) {
                $mid = $roundLote(($lo + $hi) / 2);
                $r   = self::calcEscenario(
                    $pres, $mid, $lote, $capacidad,
                    $pvpSinIva, $pvpConIva, $margenPct, $icePct,
                    $personas, $costoMoPer, $indirectCosts
                );
                if ($r['utilNeta'] >= 0) $hi = $mid;
                else $lo = $mid;
                if (abs($hi - $lo) <= $lote) break;
            }
            $peQty = (float) $roundLote($hi);
        }

        return ['escenarios' => $escenarios, 'peQty' => $peQty];
    }
}
