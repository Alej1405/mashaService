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

                                    // ── Sección: Precio de Venta Estimado ──────────────────
                                    Section::make('Precio de Venta Estimado (PVP)')
                                        ->description('PVP = Costo unitario ÷ (1 − Margen)')
                                        ->icon('heroicon-o-currency-dollar')
                                        ->schema([
                                            // Tabla de costos por ingrediente
                                            \Filament\Forms\Components\Placeholder::make('_tabla_costos')
                                                ->label('Desglose de Costos por Ingrediente')
                                                ->columnSpanFull()
                                                ->content(function (callable $get) {
                                                    $lines = $get('formulaLines') ?? [];
                                                    if (empty($lines)) {
                                                        return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Sin ingredientes registrados.</p>');
                                                    }

                                                    $lote = max((float) ($get('cantidad_minima_produccion') ?? 1), 0.0001);

                                                    $fmt = fn (float $n): string => rtrim(rtrim(number_format($n, 4), '0'), '.');

                                                    $rows       = '';
                                                    $totalLote  = 0;

                                                    foreach ($lines as $line) {
                                                        $itemId    = $line['inventory_item_id'] ?? null;
                                                        $item      = $itemId ? InventoryItem::find($itemId) : null;
                                                        $nombre    = $item?->nombre ?? ($line['notas'] ?? 'Insumo sin asignar');
                                                        $qty       = (float) ($line['cantidad'] ?? 0);
                                                        $unitId    = $line['measurement_unit_id'] ?? null;
                                                        $unitLabel = $unitId ? (MeasurementUnit::find($unitId)?->abreviatura ?? '') : '';

                                                        [$costoLote] = self::costoLinea($item, $qty, $unitId);
                                                        $costoUnidad  = $lote > 0 ? $costoLote / $lote : 0;
                                                        $qtyTotal     = $qty * $lote;
                                                        $totalLote   += $costoLote;

                                                        $rows .= "<tr class='border-b border-gray-100 dark:border-gray-700'>
                                                            <td class='py-1 pr-4 text-sm'>{$nombre}</td>
                                                            <td class='py-1 pr-4 text-sm text-right'>" . $fmt($qty) . " {$unitLabel}</td>
                                                            <td class='py-1 pr-4 text-sm text-right'>" . $fmt($qtyTotal) . " {$unitLabel}</td>
                                                            <td class='py-1 pr-4 text-sm text-right font-mono'>$ " . number_format($costoUnidad, 4) . "</td>
                                                            <td class='py-1 text-sm text-right font-mono'>$ " . number_format($costoLote, 4) . "</td>
                                                        </tr>";
                                                    }

                                                    $costoUnitarioTotal = $lote > 0 ? $totalLote / $lote : 0;

                                                    $rows .= "<tr class='border-t-2 border-gray-300 dark:border-gray-500 font-bold'>
                                                        <td class='py-2 pr-4 text-sm' colspan='3'>Total</td>
                                                        <td class='py-2 text-sm text-right font-mono'>$ " . number_format($costoUnitarioTotal, 4) . "</td>
                                                        <td class='py-2 text-sm text-right font-mono'>$ " . number_format($totalLote, 4) . "</td>
                                                    </tr>";

                                                    return new \Illuminate\Support\HtmlString("
                                                        <div class='overflow-x-auto'>
                                                            <table class='w-full'>
                                                                <thead>
                                                                    <tr class='border-b border-gray-200 dark:border-gray-600'>
                                                                        <th class='pb-1 pr-4 text-left text-xs font-semibold text-gray-500 uppercase'>Ingrediente</th>
                                                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Cant. x Unidad</th>
                                                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Cant. x Lote ({$lote})</th>
                                                                        <th class='pb-1 pr-4 text-right text-xs font-semibold text-gray-500 uppercase'>Costo Unitario</th>
                                                                        <th class='pb-1 text-right text-xs font-semibold text-gray-500 uppercase'>Costo Lote</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>{$rows}</tbody>
                                                            </table>
                                                            <p class='mt-2 text-xs text-gray-400'>* La coma (,) es separador de miles. Ej: 1,000 = mil.</p>
                                                        </div>
                                                    ");
                                                }),

                                            // Margen objetivo
                                            TextInput::make('margen_objetivo')
                                                ->label('Margen de Utilidad (%)')
                                                ->numeric()
                                                ->default(30)
                                                ->maxValue(99.99)
                                                ->suffix('%')
                                                ->live(onBlur: true)
                                                ->helperText('Ej: 40 significa que el costo representa el 60% del PVP')
                                                ->columnSpan(1),

                                            // Costo unitario
                                            \Filament\Forms\Components\Placeholder::make('_costo_unitario')
                                                ->label('Costo por Unidad')
                                                ->columnSpan(1)
                                                ->content(function (callable $get) {
                                                    $total = self::costoLote($get('formulaLines') ?? []);
                                                    $lote  = max((float) ($get('cantidad_minima_produccion') ?? 1), 0.0001);
                                                    return '$ ' . number_format(round($total / $lote, 4), 4);
                                                }),

                                            // PVP calculado
                                            \Filament\Forms\Components\Placeholder::make('_pvp_calculado')
                                                ->label('PVP Estimado')
                                                ->columnSpan(1)
                                                ->content(function (callable $get) {
                                                    $total  = self::costoLote($get('formulaLines') ?? []);
                                                    $lote   = max((float) ($get('cantidad_minima_produccion') ?? 1), 0.0001);
                                                    $margen = (float) ($get('margen_objetivo') ?? 30) / 100;
                                                    $divisor = 1 - $margen;
                                                    if ($divisor <= 0) return 'Margen inválido';
                                                    $pvp = round(($total / $lote) / $divisor, 2);
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span style="font-size:1.25rem;font-weight:bold;color:#16a34a">$ ' . number_format($pvp, 2) . '</span>'
                                                    );
                                                }),

                                            // Margen bruto
                                            \Filament\Forms\Components\Placeholder::make('_margen_bruto')
                                                ->label('Margen Bruto')
                                                ->columnSpan(1)
                                                ->content(function (callable $get) {
                                                    $total   = self::costoLote($get('formulaLines') ?? []);
                                                    $lote    = max((float) ($get('cantidad_minima_produccion') ?? 1), 0.0001);
                                                    $margen  = (float) ($get('margen_objetivo') ?? 30) / 100;
                                                    $divisor = 1 - $margen;
                                                    if ($divisor <= 0) return 'Margen inválido';
                                                    $costo = $total / $lote;
                                                    $pvp   = $costo / $divisor;
                                                    $margenBruto = $pvp > 0 ? (($pvp - $costo) / $pvp) * 100 : 0;
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span style="font-size:1.25rem;font-weight:bold;color:#2563eb">' . number_format($margenBruto, 2) . '%</span>'
                                                    );
                                                }),

                                            // Utilidad en dinero
                                            \Filament\Forms\Components\Placeholder::make('_utilidad')
                                                ->label('Utilidad por Unidad')
                                                ->columnSpan(1)
                                                ->content(function (callable $get) {
                                                    $total   = self::costoLote($get('formulaLines') ?? []);
                                                    $lote    = max((float) ($get('cantidad_minima_produccion') ?? 1), 0.0001);
                                                    $margen  = (float) ($get('margen_objetivo') ?? 30) / 100;
                                                    $divisor = 1 - $margen;
                                                    if ($divisor <= 0) return 'Margen inválido';
                                                    $costo    = $total / $lote;
                                                    $pvp      = $costo / $divisor;
                                                    $utilidad = $pvp - $costo;
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span style="font-size:1.25rem;font-weight:bold;color:#16a34a">$ ' . number_format($utilidad, 2) . '</span>'
                                                    );
                                                }),

                                            // Rentabilidad (ROI sobre costo)
                                            \Filament\Forms\Components\Placeholder::make('_rentabilidad')
                                                ->label('Rentabilidad (ROI)')
                                                ->columnSpan(1)
                                                ->content(function (callable $get) {
                                                    $total   = self::costoLote($get('formulaLines') ?? []);
                                                    $lote    = max((float) ($get('cantidad_minima_produccion') ?? 1), 0.0001);
                                                    $margen  = (float) ($get('margen_objetivo') ?? 30) / 100;
                                                    $divisor = 1 - $margen;
                                                    if ($divisor <= 0) return 'Margen inválido';
                                                    $costo         = $total / $lote;
                                                    $pvp           = $costo / $divisor;
                                                    $utilidad      = $pvp - $costo;
                                                    $rentabilidad  = $costo > 0 ? ($utilidad / $costo) * 100 : 0;
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span style="font-size:1.25rem;font-weight:bold;color:#7c3aed">' . number_format($rentabilidad, 2) . '%</span>'
                                                    );
                                                }),

                                            // ── PVP y precio distribuidor ───────────────────────
                                            TextInput::make('pvp_estimado')
                                                ->label('PVP (precio consumidor final)')
                                                ->numeric()
                                                ->default(0)
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $pvp = (float) $state;
                                                    if ($pvp > 0) {
                                                        $set('precio_distribuidor', round($pvp * 0.60, 2));
                                                    }
                                                })
                                                ->helperText('Puedes usar el PVP Estimado calculado arriba como referencia.')
                                                ->columnSpan(3),

                                            TextInput::make('precio_distribuidor')
                                                ->label('Precio Distribuidor (margen 40%)')
                                                ->numeric()
                                                ->default(0)
                                                ->prefix('$')
                                                ->readOnly()
                                                ->helperText('Calculado automáticamente: PVP × 60%. Aplica en tienda para pedidos de 10+ unidades.')
                                                ->columnSpan(3),
                                        ])
                                        ->columns(6)
                                        ->collapsible()
                                        ->columnSpanFull(),
                                ])
                                ->columns(6)
                                ->defaultItems(0)
                                ->itemLabel(function (array $state, callable $get): string {
                                    if (!$get('tiene_multiples_presentaciones')) {
                                        return 'Fórmula';
                                    }
                                    $label = $state['nombre'] ?? 'Nueva presentación';
                                    if (isset($state['pvp_estimado']) && $state['pvp_estimado'] > 0) {
                                        $label .= '  —  PVP $' . number_format($state['pvp_estimado'], 2);
                                    }
                                    return $label;
                                })
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Pasos de Producción')
                        ->icon('heroicon-o-queue-list')
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
                        ]),

                    Tab::make('Costos Indirectos')
                        ->icon('heroicon-o-calculator')
                        ->schema([

                            // ── Selector de presentación (solo UI) ───────────────
                            Select::make('_ci_presentation_id')
                                ->label('Presentación de referencia')
                                ->helperText('Selecciona la presentación para calcular el costo total real por unidad.')
                                ->options(function (callable $get) {
                                    $presentations = $get('presentations') ?? [];
                                    $result = [];
                                    $i = 1;
                                    foreach ($presentations as $key => $p) {
                                        $nombre = $p['nombre'] ?? ('Presentación ' . $i);
                                        $result[$key] = $nombre;
                                        $i++;
                                    }
                                    return $result;
                                })
                                ->dehydrated(false)
                                ->live()
                                ->placeholder('Seleccionar presentación...')
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

                                            if (empty($rows)) {
                                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400">Sin costos indirectos registrados.</p>');
                                            }

                                            $porUnidadTotal = $capacidad > 0 ? $totalMes / $capacidad : 0;

                                            // Fila de total
                                            $rows .= "<tr class='border-t-2 border-gray-300 dark:border-gray-500 font-bold'>
                                                <td class='py-2 pr-4 text-sm' colspan='3'>Total Indirectos / Mes</td>
                                                <td class='py-2 pr-4 text-sm text-right font-mono'>$ " . number_format($totalMes, 2) . "</td>
                                                <td class='py-2 text-sm text-right font-mono'>" . ($capacidad > 0 ? '$ ' . number_format($porUnidadTotal, 4) : '—') . "</td>
                                            </tr>";

                                            // Fila costo total real (materiales + indirectos)
                                            $presKey = $get('_ci_presentation_id');
                                            $presentations = $get('presentations') ?? [];
                                            $pres = ($presKey !== null && $presKey !== '') ? ($presentations[$presKey] ?? null) : null;

                                            if ($pres) {
                                                $costoMat      = self::costoLote($pres['formulaLines'] ?? []);
                                                $lote          = max((float) ($pres['cantidad_minima_produccion'] ?? 1), 0.0001);
                                                $matPorUnidad  = $costoMat / $lote;
                                                $costoTotal    = $matPorUnidad + $porUnidadTotal;
                                                $rows .= "<tr class='border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800'>
                                                    <td class='py-2 pr-4 text-sm font-semibold' colspan='3'>Costo Total Real / Unidad (materiales + indirectos)</td>
                                                    <td class='py-2 pr-4 text-sm text-right font-mono text-gray-400'>Materiales: $ " . number_format($matPorUnidad, 4) . "</td>
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

                    Tab::make('Planificación por Producción')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([

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

                                    TextInput::make('_plan_num_personas')
                                        ->label('Personas (simulación)')
                                        ->numeric()
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->placeholder(fn (callable $get) => 'Por defecto: ' . ($get('num_personas') ?? '0'))
                                        ->helperText('Deja vacío para usar el valor configurado en Costos Indirectos.')
                                        ->columnSpan(1),

                                    TextInput::make('_plan_costo_mo_persona')
                                        ->label('Costo Mano de Obra / persona (simulación)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->placeholder(fn (callable $get) => 'Por defecto: $' . number_format((float) ($get('costo_mano_obra_persona') ?? 0), 2))
                                        ->helperText('Deja vacío para usar el valor configurado.')
                                        ->columnSpan(1),
                                ]),

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
                                        })
                                        ->placeholder('Ej: 2.50')
                                        ->helperText('Ingresa el PVP → se calcula el margen.')
                                        ->columnSpan(1),

                                    Toggle::make('_plan_pvp_incluye_iva')
                                        ->label('¿El PVP ya incluye IVA (15%)?')
                                        ->dehydrated(false)
                                        ->live()
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
                                        ->helperText('ROI mínimo esperado. Por cada +2% adicional, el tiempo de retorno estimado sube ~15 días.')
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
                                    $costoTotalProd = $totalMatComprar + $totalMatStock + $totalInd;
                                    $inversionReal  = $totalMatComprar + $totalInd;
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

                                    // ── C3b: Canal Distribuidores ────────────────────
                                    $pvpDistribuidor       = round($pvpSinIva * 0.60, 4);
                                    $ingresoDistribuidor   = round($pvpDistribuidor * $cantidad, 2);
                                    $utilidadDistribuidor  = round($ingresoDistribuidor - $costoTotalProd - $iceTotal, 2);
                                    $margenDistribuidor    = $ingresoDistribuidor > 0
                                        ? round(($utilidadDistribuidor / $ingresoDistribuidor) * 100, 1) : 0;
                                    $roiDistribuidor       = $inversionReal > 0
                                        ? round(($utilidadDistribuidor / $inversionReal) * 100, 1) : 0;
                                    $utilUnitDist          = $cantidad > 0 ? $utilidadDistribuidor / $cantidad : 0;
                                    $cUtilDist             = $utilidadDistribuidor >= 0 ? $cGreen : $cRed;

                                    $html .= '<div style="margin-bottom:1.25rem;border-radius:0.75rem;border:1px solid #c7d2fe;overflow:hidden;">'
                                        . '<div style="background:#eef2ff;padding:0.5rem 1rem;border-bottom:1px solid #c7d2fe;display:flex;justify-content:space-between;align-items:center;">'
                                        . '<span style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#4338ca;">📦 Canal Distribuidores — si toda la producción se vende a distribuidores</span>'
                                        . '<span style="font-size:0.75rem;color:#6366f1;font-weight:600;">Precio: $ ' . $fmt($pvpDistribuidor) . ' / u. (PVP × 60%)</span>'
                                        . '</div>'
                                        . '<div style="display:grid;grid-template-columns:repeat(5,1fr);">';
                                    $html .= $kpiBig('Precio Distribuidor', '$ ' . $fmt($pvpDistribuidor), 'vs PVP $ ' . $fmt($pvpSinIva) . ' público', '#6366f1');
                                    $html .= $kpiBig('Ingreso Bruto', '$ ' . $fmt($ingresoDistribuidor), 'vs $ ' . $fmt($ingresoNeto) . ' canal directo', '#6366f1');
                                    $html .= $kpiBig('Margen', $pct($margenDistribuidor), 'vs ' . $pct($margenNeto) . ' canal directo', $cUtilDist);
                                    $html .= $kpiBig('ROI', $pct($roiDistribuidor), 'vs ' . $pct($roi) . ' canal directo', $cUtilDist);
                                    $html .= $kpiBig('Utilidad Neta', '$ ' . $fmt($utilidadDistribuidor), '$ ' . number_format($utilUnitDist, 4) . ' / u.', $cUtilDist, true);
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

                                    // ── C8: Nota al pie ──────────────────────────────
                                    $html .= '<p style="margin-top:0.75rem;font-size:0.68rem;color:' . $cGray . ';line-height:1.6;">'
                                        . '* <strong>IVA</strong>: passthrough — lo cobras al cliente y lo remites al SRI, no impacta tu utilidad. '
                                        . '* <strong>Payback</strong>: días necesarios para recuperar la inversión con los ingresos de ventas. '
                                        . ($aplicaIce ? '* <strong>ICE</strong>: calculado sobre el precio ex-fábrica (PVP sin IVA). Verifique tarifas vigentes con el SRI. ' : '')
                                        . '* Los materiales en stock se consideran en el costo total pero no en la inversión a desembolsar.'
                                        . '</p>';

                                    return new \Illuminate\Support\HtmlString($html);
                                }),

                            // ── Acción guardar simulación ─────────────────────────
                            \Filament\Forms\Components\Actions::make([
                                \Filament\Forms\Components\Actions\Action::make('guardar_simulacion')
                                    ->label('💾 Guardar como Simulación')
                                    ->color('success')
                                    ->icon('heroicon-o-bookmark')
                                    ->form([
                                        \Filament\Forms\Components\TextInput::make('nombre_sim')
                                            ->label('Nombre de la Simulación')
                                            ->required()
                                            ->placeholder('Ej: Producción Octubre 2026 — Escenario A')
                                            ->maxLength(150),
                                        \Filament\Forms\Components\Textarea::make('notas_sim')
                                            ->label('Notas opcionales')
                                            ->rows(2),
                                    ])
                                    ->action(function (array $data, callable $get, $record) {
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
                                        $costoTotal  = $totalMat + $totalInd;
                                        $costoUnitario = $cantidad > 0 ? $costoTotal / $cantidad : 0;
                                        if ($pvpSinIva <= 0 && $margenPct > 0) {
                                            $div = 1 - $margenPct / 100;
                                            $pvpSinIva = $div > 0 ? round($costoUnitario / $div, 2) : 0;
                                        }
                                        $ingresoNeto  = $pvpSinIva * $cantidad;
                                        $ivaTotal     = round($ingresoNeto * 0.15, 2);
                                        $icePct       = (bool) ($get('_plan_aplica_ice') ?? false) ? (float) ($get('_plan_ice_porcentaje') ?? 0) / 100 : 0;
                                        $iceTotal     = round($ingresoNeto * $icePct, 2);
                                        $utilBruta    = $ingresoNeto - $costoTotal;
                                        $utilNeta     = $utilBruta - $iceTotal;
                                        $margenBruto  = $ingresoNeto > 0 ? ($utilBruta / $ingresoNeto) * 100 : 0;
                                        $margenNeto   = $ingresoNeto > 0 ? ($utilNeta / $ingresoNeto) * 100 : 0;
                                        $roi          = $costoTotal > 0 ? ($utilNeta / $costoTotal) * 100 : 0;
                                        $diasVenta    = (int) ($get('_plan_dias_venta') ?? 0);
                                        $ingresoDiario = $diasVenta > 0 ? $ingresoNeto / $diasVenta : 0;
                                        $payback      = $ingresoDiario > 0 ? round($costoTotal / $ingresoDiario, 1) : null;

                                        \App\Models\ProductSimulation::create([
                                            'empresa_id'         => \Filament\Facades\Filament::getTenant()->id,
                                            'product_design_id'  => $record->id,
                                            'nombre'             => $data['nombre_sim'],
                                            'presentation_nombre'=> $pres['nombre'] ?? null,
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
                                            'estado'             => 'borrador',
                                        ]);

                                        \Filament\Notifications\Notification::make()
                                            ->title('Simulación guardada')
                                            ->body("La simulación \"{$data['nombre_sim']}\" fue guardada exitosamente.")
                                            ->success()
                                            ->send();
                                    }),
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

        return $cantidad > 0 ? ($totalMat + $totalInd) / $cantidad : 0;
    }
}
