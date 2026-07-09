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
    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $navigationGroup = 'Producto';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel      = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';

    // REEMPLAZADO: el módulo Producto ahora vive en StoreProductResource sobre la
    // tabla única store_products. Este recurso (sobre product_designs/costos) queda
    // OCULTO. El archivo se conserva solo porque ProduccionPage usa costoLinea().
    public static function canAccess(): bool
    {
        return false;
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
                            Toggle::make('publicado_catalogo')
                                ->label('Publicar en catálogo web')
                                ->helperText('Aparece en la API pública del sitio web y ecommerce.')
                                ->default(false)
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
        $suLabel      = $item->measurementUnit?->abreviatura ?? '';
        $puLabel      = $item->purchaseUnit?->abreviatura ?? $suLabel;

        // Si el ítem tiene presentación (ej. bidón de 20L), el precio es por presentación
        // y el factor efectivo = conversion_factor × capacidad_presentación
        // Ej: 1000 ml/litro × 20 litros/bidón = 20,000 ml/bidón → $4 / 20,000 = $0.0002/ml
        if ($item->presentation_id) {
            $pres            = $item->presentation;
            $capacidadPres   = max((float) ($pres?->capacidad ?? 1), 0.000001);
            $factorEfectivo  = $factor * $capacidadPres;
            $costo           = ($precioCompra / $factorEfectivo) * $qty;
            $presLabel       = $pres?->nombre ?? 'presentación';
            $detalle         = "\${$precioCompra}/{$presLabel} ÷ {$factorEfectivo} × {$qty} {$suLabel}";
        } elseif ($item->purchase_unit_id && $unitId == $item->purchase_unit_id) {
            // Sin presentación, fórmula en unidad de compra → precio directo
            $costo   = $precioCompra * $qty;
            $detalle = "\${$precioCompra} × {$qty} {$puLabel}";
        } else {
            // Sin presentación, fórmula en unidad de stock → dividir por factor
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
            [$cLinea] = self::costoLinea($item, $cantNecStock, $stockUnitIdT);
            $totalMat += $cLinea;
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
