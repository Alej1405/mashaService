<?php

namespace App\Filament\App\Pages;

use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\ProductionOrder;
use App\Models\ProductionMaterial;
use App\Models\ProductionPlan;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class ProduccionPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Producción';
    protected static ?string $title           = 'Producción';
    protected static ?string $navigationGroup = 'Planificación y Producción';
    protected static ?int    $navigationSort  = 2;

    protected static string $view = 'filament.app.pages.produccion';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('produccion');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // ── Configurar etapas y generar órdenes ──────────────────────────────
    public function configurarProduccionAction(): Action
    {
        $page = $this;

        return Action::make('configurarProduccion')
            ->modalHeading(fn (array $arguments) => 'Crear etapas de producción: ' . ($arguments['nombre'] ?? ''))
            ->modalWidth('5xl')
            ->modalSubmitActionLabel('✅ Crear etapas')
            ->mountUsing(function (\Filament\Forms\Form $form, array $arguments) {
                $plan = ProductionPlan::with('simulation.productDesign')->find($arguments['plan_id'] ?? null);
                if (!$plan?->simulation) {
                    $form->fill([
                        '_analisis_html'   => '<p style="color:#ef4444">No se encontró el plan.</p>',
                        'etapas'           => [],
                        '_plan_id'         => null,
                        '_presentation_id' => null,
                        '_total_unidades'  => 0,
                    ]);
                    return;
                }

                $sim           = $plan->simulation;
                $totalUnidades = (float) $sim->cantidad;

                $presentation = ProductPresentation::where('product_design_id', $sim->product_design_id)
                    ->where('nombre', $sim->presentation_nombre)
                    ->with(['formulaLines.inventoryItem.measurementUnit'])
                    ->first();

                [$analisisHtml] = $this->calcularAnalisisStock($presentation, $totalUnidades);

                $form->fill([
                    '_analisis_html'      => $analisisHtml,
                    '_plan_id'            => $plan->id,
                    '_presentation_id'    => $presentation?->id,
                    '_total_unidades'     => $totalUnidades,
                    '_fecha_inicio_plan'  => $plan->fecha_inicio?->toDateString(),
                    '_fecha_fin_plan'     => $plan->fecha_fin?->toDateString(),
                    'etapas'              => [],
                ]);
            })
            ->form([
                // ── Resumen de stock total para la simulación ────────────────
                Placeholder::make('_analisis_html')
                    ->label('')
                    ->content(fn (\Filament\Forms\Get $get) => new \Illuminate\Support\HtmlString($get('_analisis_html') ?? ''))
                    ->columnSpanFull(),

                // ── Resumen de unidades planificadas vs total ────────────────
                Placeholder::make('_resumen_etapas')
                    ->label('')
                    ->live()
                    ->content(function (\Filament\Forms\Get $get) {
                        $total     = (float) ($get('_total_unidades') ?? 0);
                        $etapas    = $get('etapas') ?? [];
                        $planif    = collect($etapas)->sum(fn ($e) => (float) ($e['cantidad'] ?? 0));
                        $faltante  = max(0, $total - $planif);
                        $pct       = $total > 0 ? min(100, round(($planif / $total) * 100)) : 0;
                        $color     = $faltante <= 0 ? '#16a34a' : ($planif > 0 ? '#d97706' : '#6b7280');
                        $bgBar     = $faltante <= 0 ? '#16a34a' : '#6366f1';

                        $html  = '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.75rem 1rem;display:flex;align-items:center;gap:1.5rem;">';
                        $html .= '<div style="flex:1;">';
                        $html .= '<div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#64748b;margin-bottom:0.35rem;">';
                        $html .= '<span>Unidades planificadas: <strong style="color:#111827;">' . number_format($planif, 0) . '</strong> / ' . number_format($total, 0) . '</span>';
                        $html .= "<span style='font-weight:600;color:{$color};'>" . ($faltante > 0 ? 'Faltan: ' . number_format($faltante, 0) . ' u.' : '✓ Total cubierto') . '</span>';
                        $html .= '</div>';
                        $html .= '<div style="background:#e2e8f0;border-radius:999px;height:6px;overflow:hidden;">';
                        $html .= "<div style='background:{$bgBar};height:6px;width:{$pct}%;transition:width 0.3s;'></div>";
                        $html .= '</div></div></div>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->columnSpanFull(),

                // ── Repeater de etapas ───────────────────────────────────────
                \Filament\Forms\Components\Repeater::make('etapas')
                    ->label('Etapas')
                    ->addActionLabel('+ Agregar etapa')
                    ->minItems(1)
                    ->schema([
                        TextInput::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Ej: Producir 140 botellas con stock actual')
                            ->required()
                            ->columnSpan(4),

                        TextInput::make('cantidad')
                            ->label('Unidades')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->live(onBlur: true)
                            ->columnSpan(2),

                        DatePicker::make('fecha_inicio')
                            ->label('Fecha inicio')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (\Filament\Forms\Get $get) => $get('../../_fecha_inicio_plan'))
                            ->maxDate(fn (\Filament\Forms\Get $get) => $get('../../_fecha_fin_plan'))
                            ->columnSpan(2),

                        DatePicker::make('fecha_fin')
                            ->label('Fecha fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (\Filament\Forms\Get $get) => $get('fecha_inicio') ?? $get('../../_fecha_inicio_plan'))
                            ->maxDate(fn (\Filament\Forms\Get $get) => $get('../../_fecha_fin_plan'))
                            ->columnSpan(2),

                        // ── Detalle de stock reactivo para esta etapa ────────
                        Placeholder::make('_stock_etapa')
                            ->label('')
                            ->live()
                            ->content(function (\Filament\Forms\Get $get) use ($page) {
                                $presId   = $get('../../_presentation_id');
                                $cantidad = (float) ($get('cantidad') ?? 0);

                                if (!$presId || $cantidad <= 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<p style="font-size:0.77rem;color:#9ca3af;padding:0.25rem 0;">Ingresa la cantidad para ver el análisis de stock de esta etapa.</p>'
                                    );
                                }

                                $presentation = ProductPresentation::with(['formulaLines.inventoryItem.measurementUnit'])
                                    ->find($presId);
                                if (!$presentation) return new \Illuminate\Support\HtmlString('');

                                $lote         = max((float) $presentation->cantidad_minima_produccion, 0.0001);
                                $hayFaltantes = false;

                                $html  = '<div style="margin-top:0.25rem;border:1px solid #e2e8f0;border-radius:0.5rem;overflow:hidden;">';
                                $html .= '<div style="background:#f1f5f9;padding:0.3rem 0.75rem;font-size:0.73rem;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">Stock para ' . number_format($cantidad, 0) . ' unidades</div>';
                                $html .= '<table style="width:100%;border-collapse:collapse;">';
                                $html .= '<thead><tr style="background:#f8fafc;">';
                                $html .= '<th style="text-align:left;padding:0.3rem 0.6rem;font-size:0.71rem;color:#64748b;font-weight:600;">Insumo</th>';
                                $html .= '<th style="text-align:right;padding:0.3rem 0.6rem;font-size:0.71rem;color:#64748b;font-weight:600;">Necesario</th>';
                                $html .= '<th style="text-align:right;padding:0.3rem 0.6rem;font-size:0.71rem;color:#64748b;font-weight:600;">En stock</th>';
                                $html .= '<th style="text-align:right;padding:0.3rem 0.6rem;font-size:0.71rem;color:#64748b;font-weight:600;">Faltante</th>';
                                $html .= '</tr></thead><tbody>';

                                foreach ($presentation->formulaLines as $line) {
                                    $item = $line->inventoryItem;
                                    if (!$item) continue;

                                    $cantNec   = $page->cantNecesariaStockPublic($line, $cantidad, $lote, $item);
                                    $stock     = max(0, (float) $item->stock_actual);
                                    $gap       = max(0, $cantNec - $stock);
                                    $unitLabel = $item->measurementUnit?->abreviatura ?? '';

                                    if ($gap > 0) $hayFaltantes = true;

                                    $gapColor = $gap > 0 ? '#dc2626' : '#16a34a';
                                    $bg       = $gap > 0 ? '#fef2f2' : '#f0fdf4';

                                    $html .= "<tr style=\"background:{$bg};border-bottom:1px solid #f1f5f9;\">";
                                    $html .= '<td style="padding:0.3rem 0.6rem;font-size:0.78rem;color:#374151;">' . e($item->nombre) . '</td>';
                                    $html .= '<td style="padding:0.3rem 0.6rem;font-size:0.77rem;text-align:right;color:#374151;">' . number_format($cantNec, 2) . ' ' . e($unitLabel) . '</td>';
                                    $html .= '<td style="padding:0.3rem 0.6rem;font-size:0.77rem;text-align:right;color:#374151;">' . number_format($stock, 2) . ' ' . e($unitLabel) . '</td>';
                                    $html .= "<td style=\"padding:0.3rem 0.6rem;font-size:0.77rem;text-align:right;font-weight:700;color:{$gapColor};\">"
                                        . ($gap > 0 ? number_format($gap, 2) . ' ' . e($unitLabel) : '✓')
                                        . '</td>';
                                    $html .= '</tr>';
                                }

                                $html .= '</tbody></table>';
                                if ($hayFaltantes) {
                                    $html .= '<div style="padding:0.35rem 0.75rem;background:#fffbeb;border-top:1px solid #fde68a;font-size:0.74rem;color:#92400e;">⚠ Hay insumos insuficientes. Activa la opción de abajo para generar la orden de compra.</div>';
                                } else {
                                    $html .= '<div style="padding:0.35rem 0.75rem;background:#f0fdf4;border-top:1px solid #bbf7d0;font-size:0.74rem;color:#15803d;">✓ Stock suficiente para esta etapa.</div>';
                                }
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),

                        Toggle::make('crear_compra')
                            ->label('Crear orden de compra para los insumos faltantes de esta etapa')
                            ->default(false)
                            ->columnSpanFull(),

                        TextInput::make('notas')
                            ->label('Notas')
                            ->placeholder('Opcional')
                            ->columnSpanFull(),
                    ])
                    ->columns(8)
                    ->columnSpanFull(),

                Hidden::make('_plan_id'),
                Hidden::make('_presentation_id'),
                Hidden::make('_total_unidades'),
                Hidden::make('_fecha_inicio_plan'),
                Hidden::make('_fecha_fin_plan'),
            ])
            ->action(function (array $data): void {
                $plan = ProductionPlan::with('simulation')->find($data['_plan_id']);
                if (!$plan) return;

                $sim      = $plan->simulation;
                $empresaId = $plan->empresa_id;
                $empresa  = Empresa::find($empresaId);
                $presId   = (int) $data['_presentation_id'];
                $etapas   = $data['etapas'] ?? [];

                $presentation = ProductPresentation::with(['formulaLines.inventoryItem.presentation'])->find($presId);
                if (!$presentation || empty($etapas)) return;

                $lote = max((float) $presentation->cantidad_minima_produccion, 0.0001);

                $ordersCreados = [];
                $purchaseRefs  = [];

                foreach ($etapas as $i => $etapa) {
                    $cantEtapa    = max(1, (float) ($etapa['cantidad'] ?? 1));
                    $descripcion  = $etapa['descripcion'] ?? "Etapa " . ($i + 1);
                    $crearCompra  = (bool) ($etapa['crear_compra'] ?? false);
                    $notasEtapa   = $etapa['notas'] ?? null;
                    $fechaInicio  = $etapa['fecha_inicio'] ? \Carbon\Carbon::parse($etapa['fecha_inicio']) : ($plan->fecha_inicio ?? now());
                    $fechaFin     = $etapa['fecha_fin']    ? \Carbon\Carbon::parse($etapa['fecha_fin'])    : $fechaInicio;

                    // Calcular faltantes para esta etapa (siempre, no solo cuando se solicita compra)
                    $faltantes = [];
                    foreach ($presentation->formulaLines as $line) {
                        $item = $line->inventoryItem;
                        if (!$item) continue;
                        $cantNec = $this->cantNecesariaStock($line, $cantEtapa, $lote, $item);
                        $gap     = max(0, $cantNec - (float) $item->stock_actual);
                        if ($gap > 0) {
                            // Convertir gap (unidades base) a unidades de presentación de compra
                            $purchPres = $item->presentation; // InventoryItem.presentation_id → ItemPresentation

                            if ($purchPres && $purchPres->activo) {
                                $factor    = (float) $purchPres->capacidad * max((float) $purchPres->factor_conversion, 1.0);
                                $qtyCompra = $factor > 0 ? $gap / $factor : $gap;
                                $precio    = (float) $item->purchase_price;
                            } else {
                                $cf        = max((float) $item->conversion_factor, 1.0);
                                $qtyCompra = $gap / $cf;
                                $precio    = (float) $item->purchase_price;
                            }

                            $faltantes[] = [
                                'item_id'        => $item->id,
                                'qty'            => $qtyCompra,
                                'purchase_price' => $precio,
                                'unidad'         => $purchPres?->nombre ?? $item->purchaseUnit?->nombre ?? '',
                            ];
                        }
                    }

                    $hasFaltantes = !empty($faltantes);

                    // Crear orden de compra en borrador si el toggle está activo y hay faltantes
                    // La contabilidad solo se activa al confirmar desde el módulo de Compras
                    if ($crearCompra && $hasFaltantes) {
                        $notaItems = collect($faltantes)
                            ->map(fn ($f) => number_format($f['qty'], 2) . ' ' . $f['unidad'])
                            ->implode(', ');
                        $purchase = Purchase::create([
                            'empresa_id' => $empresaId,
                            'date'       => $fechaInicio->toDateString(),
                            'status'     => 'borrador',
                            'tipo_pago'  => 'contado',
                            'forma_pago' => 'efectivo',
                            'notas'      => "Orden de compra automática — {$descripcion} · {$sim->nombre} | Insumos: {$notaItems}",
                        ]);
                        foreach ($faltantes as $f) {
                            PurchaseItem::create([
                                'purchase_id'       => $purchase->id,
                                'inventory_item_id' => $f['item_id'],
                                'quantity'          => $f['qty'],
                                'unit_price'        => $f['purchase_price'],
                                'aplica_iva'        => false,
                            ]);
                        }
                        $purchase->update([
                            'subtotal' => $purchase->items()->sum('subtotal'),
                            'iva'      => $purchase->items()->sum('iva_monto'),
                            'total'    => $purchase->items()->sum('total_item'),
                        ]);
                        $purchaseRefs[] = $purchase->number;
                    }

                    // Estado de la orden: bloqueada si hay insumos insuficientes
                    $orderEstado = $hasFaltantes ? 'abastecimiento' : 'borrador';

                    // Orden de producción para esta etapa
                    $order = ProductionOrder::create([
                        'empresa_id'              => $empresaId,
                        'production_plan_id'      => $plan->id,
                        'fecha'                   => $fechaInicio->toDateString(),
                        'fecha_fin'               => $fechaFin->toDateString(),
                        'product_presentation_id' => $presId,
                        'cantidad_producida'       => $cantEtapa,
                        'costo_total'             => 0,
                        'estado'                  => $orderEstado,
                        'notas'                   => $descripcion . ($notasEtapa ? " · {$notasEtapa}" : ''),
                    ]);

                    foreach ($presentation->formulaLines as $line) {
                        $item = $line->inventoryItem;
                        if (!$item) continue;
                        $cantNec = $this->cantNecesariaStock($line, $cantEtapa, $lote, $item);
                        [$costoTotal] = \App\Filament\App\Resources\ProductDesignResource::costoLinea(
                            $item, $cantNec, $item->measurement_unit_id
                        );
                        $costoU = $cantNec > 0 ? $costoTotal / $cantNec : 0;
                        ProductionMaterial::create([
                            'production_order_id' => $order->id,
                            'inventory_item_id'   => $item->id,
                            'cantidad_consumida'  => $cantNec,
                            'costo_unitario'      => $costoU,
                        ]);
                    }

                    $ordersCreados[] = $order->referencia;
                }

                $plan->update(['estado' => 'en_proceso']);

                $this->enviarNotificacion($plan, $sim->nombre, $ordersCreados, $purchaseRefs, $empresa);

                $msg = count($ordersCreados) . ' orden(es) de producción creada(s): ' . implode(', ', $ordersCreados) . '.';
                if (!empty($purchaseRefs)) $msg .= ' Órdenes de compra en borrador: ' . implode(', ', $purchaseRefs) . '. Confírmalas en el módulo de Compras para registrar el movimiento contable.';

                Notification::make()
                    ->title('Producción configurada')
                    ->body($msg)
                    ->success()
                    ->send();

                $this->redirect(static::getUrl());
            });
    }

    // ── Cambiar estado del plan ───────────────────────────────────────────
    public function cambiarEstadoAction(): Action
    {
        return Action::make('cambiarEstado')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => 'Cambiar estado a "' . ucfirst(str_replace('_', ' ', $arguments['nuevo_estado'] ?? '')) . '"')
            ->modalDescription(fn (array $arguments) => match ($arguments['nuevo_estado'] ?? '') {
                'sin_stock'  => 'Se marcará este plan como bloqueado por falta de stock.',
                'en_proceso' => 'Se reanudará la producción de este plan.',
                'finalizado' => 'Se marcará la producción como completada.',
                'despachado' => 'Se marcará el lote como despachado al cliente.',
                default      => '¿Confirmas el cambio de estado?',
            })
            ->action(function (array $arguments): void {
                $plan = ProductionPlan::find($arguments['plan_id'] ?? null);
                if (!$plan) return;

                if ($arguments['nuevo_estado'] === 'finalizado' &&
                    $plan->productionOrders()->where('estado', 'abastecimiento')->exists()) {
                    Notification::make()
                        ->title('No se puede finalizar')
                        ->body('Hay etapas en estado de abastecimiento. Completa el stock faltante antes de finalizar.')
                        ->warning()
                        ->send();
                    return;
                }

                $plan->update(['estado' => $arguments['nuevo_estado']]);
                $this->redirect(static::getUrl());
            });
    }

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();

        $planes = ProductionPlan::where('empresa_id', $empresa->id)
            ->with(['simulation.productDesign', 'productionOrders'])
            ->whereNotNull('product_simulation_id')
            ->orderByRaw("FIELD(estado,'en_proceso','sin_stock','en_proyecto','finalizado','despachado')")
            ->orderBy('fecha_inicio')
            ->get();

        return compact('planes');
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    public function cantNecesariaStockPublic($line, float $cantUnidades, float $lote, InventoryItem $item): float
    {
        return $this->cantNecesariaStock($line, $cantUnidades, $lote, $item);
    }

    private function cantNecesariaStock($line, float $cantUnidades, float $lote, InventoryItem $item): float
    {
        $needed = ((float) $line->cantidad / $lote) * $cantUnidades;

        // Misma unidad o sin unidad definida: comparación directa contra stock_actual
        if (! $line->measurement_unit_id || $line->measurement_unit_id === $item->measurement_unit_id) {
            return $needed;
        }

        // Fórmula usa la unidad de compra (ej: Litros), stock está en unidad base (ej: ml)
        // conversion_factor = unidades_base por unidad_de_compra (ej: 1000 ml/Litro)
        if ($item->purchase_unit_id && $line->measurement_unit_id === $item->purchase_unit_id) {
            return $needed * max((float) $item->conversion_factor, 0.000001);
        }

        // Fórmula en unidad base, stock también en unidad base — caso redundante, pero seguro
        return $needed;
    }

    private function calcularAnalisisStock(?ProductPresentation $presentation, float $totalUnidades): array
    {
        if (!$presentation || $presentation->formulaLines->isEmpty()) {
            return [
                '<p style="color:#9ca3af;text-align:center;padding:1rem;">Sin fórmula definida para esta presentación.</p>',
                1,
            ];
        }

        $lote = max((float) $presentation->cantidad_minima_produccion, 0.0001);
        $minUnitsCubiertas = PHP_INT_MAX;
        $hayFaltantes      = false;

        $html  = '<div style="margin-bottom:1rem;">';
        $html .= '<p style="font-size:0.8rem;color:#6b7280;margin-bottom:0.75rem;">Análisis de stock para producir <strong>' . number_format($totalUnidades, 0) . ' unidades</strong> de <em>' . e($presentation->nombre) . '</em>:</p>';
        $html .= '<table style="width:100%;border-collapse:collapse;font-size:0.82rem;">';
        $html .= '<thead><tr style="background:#f1f5f9;">';
        $html .= '<th style="text-align:left;padding:0.4rem 0.75rem;color:#475569;font-weight:600;">Insumo</th>';
        $html .= '<th style="text-align:right;padding:0.4rem 0.75rem;color:#475569;font-weight:600;">Necesario</th>';
        $html .= '<th style="text-align:right;padding:0.4rem 0.75rem;color:#475569;font-weight:600;">En stock</th>';
        $html .= '<th style="text-align:right;padding:0.4rem 0.75rem;color:#475569;font-weight:600;">Faltante</th>';
        $html .= '<th style="text-align:right;padding:0.4rem 0.75rem;color:#475569;font-weight:600;">Cubre</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($presentation->formulaLines as $line) {
            $item = $line->inventoryItem;
            if (!$item) continue;

            $cantNecStock  = $this->cantNecesariaStock($line, $totalUnidades, $lote, $item);
            $stock         = max(0, (float) $item->stock_actual);
            $gap           = max(0, $cantNecStock - $stock);
            $unitLabel     = $item->measurementUnit?->abreviatura ?? '';
            $unitsCubiertas = $cantNecStock > 0 ? floor(($stock / $cantNecStock) * $totalUnidades) : $totalUnidades;

            if ($gap > 0) $hayFaltantes = true;
            if ($unitsCubiertas < $minUnitsCubiertas) $minUnitsCubiertas = $unitsCubiertas;

            $gapColor   = $gap > 0 ? '#dc2626' : '#16a34a';
            $cubreColor = $unitsCubiertas >= $totalUnidades ? '#16a34a' : '#d97706';
            $bg         = $gap > 0 ? '#fef2f2' : '#f0fdf4';

            $html .= "<tr style=\"background:{$bg};border-bottom:1px solid #f1f5f9;\">";
            $html .= '<td style="padding:0.4rem 0.75rem;color:#111827;">' . e($item->nombre) . '</td>';
            $html .= '<td style="padding:0.4rem 0.75rem;text-align:right;color:#374151;">' . number_format($cantNecStock, 2) . ' ' . e($unitLabel) . '</td>';
            $html .= '<td style="padding:0.4rem 0.75rem;text-align:right;color:#374151;">' . number_format($stock, 2) . ' ' . e($unitLabel) . '</td>';
            $html .= "<td style=\"padding:0.4rem 0.75rem;text-align:right;font-weight:600;color:{$gapColor};\">" . ($gap > 0 ? number_format($gap, 2) . ' ' . e($unitLabel) : '—') . '</td>';
            $html .= "<td style=\"padding:0.4rem 0.75rem;text-align:right;font-weight:600;color:{$cubreColor};\">" . number_format($unitsCubiertas, 0) . ' u.</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        if ($hayFaltantes) {
            $html .= '<p style="margin-top:0.6rem;font-size:0.76rem;color:#d97706;">⚠ Hay insumos insuficientes. Activa la opción de órdenes de compra para cubrirlos automáticamente.</p>';
        } else {
            $html .= '<p style="margin-top:0.6rem;font-size:0.76rem;color:#16a34a;">✓ Stock suficiente para toda la producción sin necesidad de compras adicionales.</p>';
        }

        $html .= '</div>';

        $etapasSugeridas = 1;
        if ($minUnitsCubiertas < PHP_INT_MAX && $minUnitsCubiertas < $totalUnidades && $minUnitsCubiertas > 0) {
            $etapasSugeridas = (int) ceil($totalUnidades / max(1, $minUnitsCubiertas));
        }

        return [$html, min($etapasSugeridas, 10)];
    }

    private function enviarNotificacion(
        ProductionPlan $plan,
        string $nombreSim,
        array $ordersRefs,
        array $purchaseRefs,
        Empresa $empresa
    ): void {
        try {
            $emails = $this->obtenerEmails($empresa);
            if (empty($emails)) return;

            $ordenesList = implode(', ', $ordersRefs);
            $compraLine  = !empty($purchaseRefs)
                ? '<p>Órdenes de compra generadas: <strong>' . implode(', ', $purchaseRefs) . '</strong></p>'
                : '<p>No se generaron órdenes de compra.</p>';

            $html = "
            <div style='font-family:sans-serif;max-width:600px;margin:auto;padding:2rem;'>
                <h2 style='color:#1e3a8a;'>🏭 Producción configurada</h2>
                <p>Se configuró la producción para el plan <strong>" . e($nombreSim) . "</strong>.</p>
                <p>Órdenes de producción: <strong>{$ordenesList}</strong></p>
                {$compraLine}
                <p style='color:#6b7280;font-size:0.85em;'>Período: {$plan->fecha_inicio->format('d/m/Y')} — {$plan->fecha_fin->format('d/m/Y')}</p>
            </div>";

            Resend::emails()->send([
                'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                'to'      => $emails,
                'subject' => 'Producción configurada: ' . $nombreSim,
                'html'    => $html,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProduccionPage: error al enviar notificación', ['error' => $e->getMessage()]);
        }
    }

    private function obtenerEmails(Empresa $empresa): array
    {
        return collect()
            ->merge($empresa->users()->whereNotNull('email')->pluck('email'))
            ->merge($empresa->accessUsers()->whereNotNull('email')->pluck('email'))
            ->unique()
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->toArray();
    }
}
