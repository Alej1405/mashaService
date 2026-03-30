<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\Alignment;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use App\Filament\App\Resources\BankAccountResource;
use App\Filament\App\Resources\CashRegisterResource;
use App\Filament\App\Resources\CreditCardResource;
use App\Filament\App\Resources\SupplierResource;
use App\Filament\App\Resources\InventoryItemResource;
use App\Models\ItemPresentation;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;
    protected static ?string $tenantRelationshipName = 'purchases';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Registro de Compras';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $modelLabel = 'Compra';
    protected static ?string $pluralModelLabel = 'Compras';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('subtotal')->default(0)->live(),
                Forms\Components\Hidden::make('iva')->default(0)->live(),
                Forms\Components\Hidden::make('total')->default(0)->live(),
                Wizard::make([
                    Step::make('Datos de la Factura')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\Select::make('supplier_id')
                                ->label('Proveedor')
                                ->relationship('supplier', 'nombre')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Supplier $record) => "{$record->nombre} — {$record->numero_identificacion}")
                                ->required()
                                ->columnSpan(2)
                                ->createOptionModalHeading('Nuevo Proveedor')
                                ->createOptionForm(fn () => SupplierResource::getQuickCreateFormSchema())
                                ->createOptionUsing(function (array $data): int {
                                    return \App\Models\Supplier::create([
                                        ...$data,
                                        'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                    ])->getKey();
                                }),
                            Forms\Components\TextInput::make('numero_factura')
                                ->label('N° Factura del Proveedor')
                                ->placeholder('001-001-000001234')
                                ->maxLength(100)
                                ->columnSpan(1),
                            Forms\Components\DatePicker::make('date')
                                ->label('Fecha de la Factura')
                                ->default(now())
                                ->required()
                                ->native(false)
                                ->columnSpan(1),
                            Forms\Components\Select::make('forma_pago')
                                ->label('Forma de Pago')
                                ->options([
                                    'efectivo'     => 'Efectivo',
                                    'transferencia' => 'Transferencia / Débito',
                                    'tarjeta_credito' => 'Tarjeta de Crédito',
                                    'credito'      => 'Crédito (Cuentas por Pagar)',
                                ])
                                ->default('efectivo')
                                ->required()
                                ->reactive()
                                ->columnSpan(1),
                            Forms\Components\Select::make('cash_register_id')
                                ->label('Caja')
                                ->relationship('cashRegister', 'nombre', fn($query) => $query->where('activo', true))
                                ->visible(fn ($get) => $get('forma_pago') === 'efectivo')
                                ->required(fn ($get) => $get('forma_pago') === 'efectivo')
                                ->searchable()
                                ->preload()
                                ->columnSpan(1)
                                ->createOptionModalHeading('Nueva Caja')
                                ->createOptionForm(fn () => CashRegisterResource::getQuickCreateFormSchema())
                                ->createOptionUsing(function (array $data): int {
                                    return \App\Models\CashRegister::create([
                                        ...$data,
                                        'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                        'activo'     => true,
                                    ])->getKey();
                                }),
                            Forms\Components\Select::make('bank_account_id')
                                ->label('Cuenta Bancaria')
                                ->relationship('bankAccount', 'numero_cuenta', fn($query) => $query->where('activo', true))
                                ->visible(fn ($get) => $get('forma_pago') === 'transferencia')
                                ->required(fn ($get) => $get('forma_pago') === 'transferencia')
                                ->searchable()
                                ->preload()
                                ->columnSpan(1)
                                ->createOptionModalHeading('Nueva Cuenta Bancaria')
                                ->createOptionForm(fn () => BankAccountResource::getQuickCreateFormSchema())
                                ->createOptionUsing(function (array $data): int {
                                    return \App\Models\BankAccount::create([
                                        ...$data,
                                        'empresa_id'    => \Filament\Facades\Filament::getTenant()->id,
                                        'activo'        => true,
                                        'saldo_inicial' => $data['saldo_inicial'] ?? 0,
                                    ])->getKey();
                                }),
                            Forms\Components\Select::make('credit_card_id')
                                ->label('Tarjeta de Crédito')
                                ->relationship('creditCard', 'nombre', fn($query) => $query->where('activo', true))
                                ->visible(fn ($get) => $get('forma_pago') === 'tarjeta_credito')
                                ->required(fn ($get) => $get('forma_pago') === 'tarjeta_credito')
                                ->searchable()
                                ->preload()
                                ->columnSpan(1)
                                ->createOptionModalHeading('Nueva Tarjeta de Crédito')
                                ->createOptionForm(fn () => CreditCardResource::getQuickCreateFormSchema())
                                ->createOptionUsing(function (array $data): int {
                                    return \App\Models\CreditCard::create([
                                        ...$data,
                                        'empresa_id'      => \Filament\Facades\Filament::getTenant()->id,
                                        'activo'          => true,
                                        'saldo_utilizado' => 0,
                                    ])->getKey();
                                }),
                            Forms\Components\DatePicker::make('fecha_vencimiento')
                                ->label('Fecha de Vencimiento')
                                ->native(false)
                                ->visible(fn ($get) => $get('forma_pago') === 'credito')
                                ->required(fn ($get) => $get('forma_pago') === 'credito')
                                ->columnSpan(1),
                            Forms\Components\Textarea::make('notas')
                                ->label('Notas / Observaciones')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])->columns(3),

                    Step::make('Productos / Ítems')
                        ->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->live()
                                ->label('')
                                ->schema([
                                    // ── Producto ──────────────────────────────
                                    Forms\Components\Select::make('inventory_item_id')
                                        ->label('Producto / Insumo')
                                        ->relationship('inventoryItem', 'nombre')
                                        ->searchable()
                                        ->getOptionLabelFromRecordUsing(fn (InventoryItem $record) => "{$record->codigo} — {$record->nombre}")
                                        ->required()
                                        ->columnSpan(4)
                                        ->createOptionModalHeading('Nuevo Ítem de Inventario')
                                        ->createOptionForm(fn () => InventoryItemResource::getQuickCreateFormSchema())
                                        ->createOptionUsing(function (array $data): int {
                                            return \App\Models\InventoryItem::create([
                                                ...$data,
                                                'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                                'activo'     => true,
                                            ])->getKey();
                                        })
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $item = InventoryItem::find($state);
                                            if (!$item) return;

                                            $set('_conversion_factor', (float) ($item->conversion_factor ?? 1));
                                            $set('_purchase_unit_label', $item->purchaseUnit?->abreviatura ?? $item->measurementUnit?->abreviatura ?? '');
                                            $set('_stock_unit_label',    $item->measurementUnit?->abreviatura ?? '');
                                            $set('_presentation_id', null);
                                            $set('_pres_factor', 1);

                                            if ($item->purchase_price > 0) {
                                                $qty    = max((float) $get('quantity'), 1);
                                                $aplica = (bool) $get('aplica_iva');
                                                $base   = $item->purchase_price * $qty;
                                                $set('total_linea', round($aplica ? $base * 1.15 : $base, 2));
                                                $set('unit_price', $item->purchase_price);
                                            }
                                        }),

                                    // ── Presentación / Empaque (virtual, no persiste) ───────
                                    Forms\Components\Select::make('_presentation_id')
                                        ->label('Presentación / Empaque')
                                        ->helperText('Opcional. Selecciona si compras por caja, paquete, etc.')
                                        ->options(fn (callable $get) => ($iid = $get('inventory_item_id'))
                                            ? ItemPresentation::where('inventory_item_id', $iid)
                                                ->where('activo', true)
                                                ->get()
                                                ->mapWithKeys(fn ($p) => [$p->id => "{$p->nombre} (×{$p->factor_conversion})"])
                                            : [])
                                        ->nullable()
                                        ->dehydrated(false)
                                        ->live()
                                        ->columnSpan(3)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $p = $state ? ItemPresentation::find($state) : null;
                                            $set('_pres_factor', $p ? (float) $p->factor_conversion : 1);
                                        }),

                                    // ── Cantidad en presentaciones ─────────────
                                    Forms\Components\TextInput::make('_pres_qty')
                                        ->label('Cant. presentaciones')
                                        ->helperText('Ej: 3 cajas. Se multiplicará por el factor.')
                                        ->numeric()
                                        ->nullable()
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->visible(fn (callable $get) => !empty($get('_presentation_id')))
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, Forms\Contracts\HasForms $livewire) {
                                            $factor = (float) ($get('_pres_factor') ?? 1);
                                            $qty    = round((float) $state * $factor, 6);
                                            $set('quantity', max($qty, 0.000001));
                                            self::recalcularLinea($get, $set);
                                            self::updateTotals($livewire, $set);
                                        }),

                                    // ── Cantidad (en unidades de compra) ──────
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Cantidad (u. compra)')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, Forms\Contracts\HasForms $livewire) {
                                            self::recalcularLinea($get, $set);
                                            self::updateTotals($livewire, $set);
                                        }),

                                    // ── Valor en factura (entrada principal) ──
                                    Forms\Components\TextInput::make('total_linea')
                                        ->label('Valor en factura')
                                        ->numeric()
                                        ->required()
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->helperText('Ingresa el valor tal como aparece en la factura del proveedor.')
                                        ->afterStateHydrated(function ($state, callable $get, callable $set) {
                                            if (empty($state)) {
                                                $unitPrice = (float) $get('unit_price');
                                                $qty       = max((float) $get('quantity'), 0.0001);
                                                $aplica    = (bool) $get('aplica_iva');
                                                if ($unitPrice > 0) {
                                                    $base = $unitPrice * $qty;
                                                    $set('total_linea', round($aplica ? $base * 1.15 : $base, 2));
                                                }
                                            }
                                        })
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, Forms\Contracts\HasForms $livewire) {
                                            self::recalcularLinea($get, $set);
                                            self::updateTotals($livewire, $set);
                                        }),

                                    // ── ¿Aplica IVA? ──────────────────────────
                                    Forms\Components\Toggle::make('aplica_iva')
                                        ->label('¿Aplica IVA?')
                                        ->default(true)
                                        ->inline(false)
                                        ->live()
                                        ->columnSpan(1)
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, Forms\Contracts\HasForms $livewire) {
                                            self::recalcularLinea($get, $set);
                                            self::updateTotals($livewire, $set);
                                        }),

                                    // ── ¿El precio ya incluye IVA? ────────────
                                    Forms\Components\Toggle::make('iva_incluido_en_precio')
                                        ->label('¿IVA ya incluido?')
                                        ->default(true)
                                        ->inline(false)
                                        ->live()
                                        ->columnSpan(1)
                                        ->visible(fn (callable $get) => (bool) $get('aplica_iva'))
                                        ->helperText(fn (callable $get) => (bool) ($get('iva_incluido_en_precio') ?? true)
                                            ? 'El sistema desglosa el IVA del valor ingresado.'
                                            : 'El sistema agregará el 15% al valor ingresado.')
                                        ->afterStateUpdated(function ($state, callable $get, callable $set, Forms\Contracts\HasForms $livewire) {
                                            self::recalcularLinea($get, $set);
                                            self::updateTotals($livewire, $set);
                                        }),

                                    // ── Datos de conversión (ocultos, poblados al seleccionar ítem) ──
                                    Forms\Components\Hidden::make('unit_price')->default(0),
                                    Forms\Components\Hidden::make('_conversion_factor')->default(1),
                                    Forms\Components\Hidden::make('_purchase_unit_label')->default(''),
                                    Forms\Components\Hidden::make('_stock_unit_label')->default(''),
                                    Forms\Components\Hidden::make('_pres_factor')->default(1),

                                    // ── Indicador de conversión (visible solo si hay factor ≠ 1) ────
                                    Forms\Components\Placeholder::make('_conversion_display')
                                        ->label('Equivale en stock')
                                        ->columnSpan(3)
                                        ->visible(fn (callable $get) => (float) ($get('_conversion_factor') ?? 1) !== 1.0)
                                        ->content(function (callable $get) {
                                            $qty    = (float) ($get('quantity') ?? 1);
                                            $factor = (float) ($get('_conversion_factor') ?? 1);
                                            $pu     = $get('_purchase_unit_label') ?: '?';
                                            $su     = $get('_stock_unit_label') ?: '?';
                                            $stockQty = round($qty * $factor, 4);
                                            return "{$qty} {$pu}  →  {$stockQty} {$su}";
                                        }),

                                    // ── Desglose (display) ────────────────────
                                    Forms\Components\Placeholder::make('base_display')
                                        ->label('Base (sin IVA)')
                                        ->columnSpan(3)
                                        ->content(function (callable $get) {
                                            $unitPrice = (float) ($get('unit_price') ?? 0);
                                            $qty       = max((float) ($get('quantity') ?? 1), 0.0001);
                                            return '$ ' . number_format(round($unitPrice * $qty, 2), 2);
                                        }),
                                    Forms\Components\Placeholder::make('iva_display')
                                        ->label('IVA (15%)')
                                        ->columnSpan(3)
                                        ->content(function (callable $get) {
                                            $unitPrice = (float) ($get('unit_price') ?? 0);
                                            $qty       = max((float) ($get('quantity') ?? 1), 0.0001);
                                            $aplica    = (bool) $get('aplica_iva');
                                            $base      = round($unitPrice * $qty, 2);
                                            return '$ ' . number_format($aplica ? round($base * 0.15, 2) : 0, 2);
                                        }),
                                    Forms\Components\Placeholder::make('precio_unit_display')
                                        ->label('P. Unitario neto')
                                        ->columnSpan(3)
                                        ->content(function (callable $get) {
                                            $unitPrice = (float) ($get('unit_price') ?? 0);
                                            return '$ ' . number_format(round($unitPrice, 4), 4);
                                        }),
                                ])
                                ->columns(13)
                                ->defaultItems(1)
                                ->addActionLabel('+ Agregar producto')
                                ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                    self::updateTotals($livewire, $set);
                                }),
                        ]),

                    Step::make('Resumen y Totales')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('subtotal_general')
                                        ->label('Subtotal (sin IVA)')
                                        ->content(function ($get) {
                                            $items = $get('items') ?? [];
                                            $sum = collect($items)->sum(function ($i) {
                                                return (float) ($i['unit_price'] ?? 0) * (float) ($i['quantity'] ?? 0);
                                            });
                                            return '$ ' . number_format($sum, 2);
                                        }),
                                    Forms\Components\Placeholder::make('iva_total')
                                        ->label('Total IVA 15%')
                                        ->content(function ($get) {
                                            $items = $get('items') ?? [];
                                            $sum = collect($items)->sum(function ($i) {
                                                $base   = (float) ($i['unit_price'] ?? 0) * (float) ($i['quantity'] ?? 0);
                                                $aplica = (bool) ($i['aplica_iva'] ?? true);
                                                return $aplica ? $base * 0.15 : 0;
                                            });
                                            return '$ ' . number_format($sum, 2);
                                        }),
                                    Forms\Components\Placeholder::make('total_general')
                                        ->label('TOTAL FACTURA')
                                        ->extraAttributes(['style' => 'font-size:1.2rem; font-weight:bold; color:#16a34a'])
                                        ->content(function ($get) {
                                            $items = $get('items') ?? [];
                                            $total = collect($items)->sum(function ($i) {
                                                $base   = (float) ($i['unit_price'] ?? 0) * (float) ($i['quantity'] ?? 0);
                                                $aplica = (bool) ($i['aplica_iva'] ?? true);
                                                return $base * ($aplica ? 1.15 : 1);
                                            });
                                            return '$ ' . number_format($total, 2);
                                        }),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-color-primary fi-btn-color-primary fi-ac-btn-action" style="background-color: orange; color: white; padding: 0.6rem 1.2rem; border-radius: 0.5rem; font-weight: bold; border: none; cursor: pointer;">Guardar Compra</button>')),
            ]);
    }

    /**
     * Calcula unit_price neto a partir del valor ingresado en factura.
     * - aplica_iva=false           → sin IVA, unit_price = valor / qty
     * - aplica_iva=true, incluido  → el valor ya contiene IVA → desglosa ÷ 1.15
     * - aplica_iva=true, excluido  → el valor es neto → el sistema agrega el 15%
     */
    public static function recalcularLinea(callable $get, callable $set): void
    {
        $valorFactura  = (float) ($get('total_linea') ?? 0);
        $qty           = max((float) ($get('quantity') ?? 1), 0.0001);
        $aplicaIva     = (bool) $get('aplica_iva');
        $ivaIncluido   = (bool) ($get('iva_incluido_en_precio') ?? true);

        if ($aplicaIva && $ivaIncluido) {
            // El precio ya lleva IVA → desglosa para obtener base neta
            $baseLinea = $valorFactura / 1.15;
        } else {
            // Sin IVA o IVA no incluido → el valor ingresado ES la base neta
            $baseLinea = $valorFactura;
        }

        $set('unit_price', round($baseLinea / $qty, 6));
    }

    /**
     * Recalcula los totales generales de la compra a partir de unit_price (neto).
     */
    public static function updateTotals($livewire, $set): void
    {
        $items    = $livewire->data['items'] ?? [];
        $subtotal = 0;
        $iva      = 0;

        foreach ($items as $item) {
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $qty       = (float) ($item['quantity'] ?? 0);
            $aplicaIva = (bool) ($item['aplica_iva'] ?? true);

            $base    = $unitPrice * $qty;
            $ivaPart = $aplicaIva ? $base * 0.15 : 0;

            $subtotal += $base;
            $iva      += $ivaPart;
        }

        $total = $subtotal + $iva;

        $set('subtotal', round($subtotal, 2));
        $set('iva', round($iva, 2));
        $set('total', round($total, 2));

        if (isset($livewire->data) && is_array($livewire->data)) {
            $livewire->data['subtotal'] = round($subtotal, 2);
            $livewire->data['iva']      = round($iva, 2);
            $livewire->data['total']    = round($total, 2);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('N° Interno')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_factura')
                    ->label('N° Factura')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('forma_pago')
                    ->label('Pago')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'efectivo'       => 'Efectivo',
                        'transferencia'  => 'Transferencia',
                        'tarjeta_credito' => 'Tarjeta',
                        'credito'        => 'Crédito',
                        default          => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'efectivo'       => 'success',
                        'transferencia'  => 'info',
                        'tarjeta_credito' => 'warning',
                        'credito'        => 'danger',
                        default          => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Base')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('iva')
                    ->label('IVA')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'borrador'   => 'gray',
                        'confirmado' => 'success',
                        'anulado'    => 'danger',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'confirmado' => 'Confirmado',
                        'anulado' => 'Anulado',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_pago')
                    ->label('Tipo Pago')
                    ->options([
                        'contado' => 'Contado',
                        'credito_local' => 'Crédito Local',
                        'credito_exterior' => 'Crédito Exterior',
                    ]),
                Tables\Filters\Filter::make('fecha')
                    ->form([
                        Forms\Components\DatePicker::make('desde'),
                        Forms\Components\DatePicker::make('hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn ($q) => $q->whereDate('date', '>=', $data['desde']))
                            ->when($data['hasta'], fn ($q) => $q->whereDate('date', '<=', $data['hasta']));
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Action::make('confirmar')
                        ->label('Confirmar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Purchase $record) => $record->status === 'borrador')
                        ->requiresConfirmation()
                        ->action(function (Purchase $record) {
                            // Recalcular totales desde los ítems (unit_price ya es neto)
                            $subtotal = $record->items->sum(fn($i) => (float)$i->subtotal);
                            $iva      = $record->items->sum(fn($i) => (float)$i->iva_monto);

                            $record->update([
                                'status'   => 'confirmado',
                                'subtotal' => $subtotal,
                                'iva'      => $iva,
                                'total'    => $subtotal + $iva,
                            ]);

                            Notification::make()
                                ->title('Compra confirmada — asiento contable generado')
                                ->success()
                                ->send();
                        }),
                    Action::make('anular')
                        ->label('Anular')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Purchase $record) => $record->status === 'confirmado')
                        ->requiresConfirmation()
                        ->action(function (Purchase $record) {
                            DB::transaction(function () use ($record) {
                                foreach ($record->items as $item) {
                                    $factor   = (float) ($item->inventoryItem->conversion_factor ?? 1);
                                    $stockQty = round($item->quantity * $factor, 6);

                                    InventoryMovement::create([
                                        'empresa_id'        => $record->empresa_id,
                                        'inventory_item_id' => $item->inventory_item_id,
                                        'type'              => 'salida',
                                        'quantity'          => $stockQty,
                                        'unit_price'        => $item->unit_price / $factor,
                                        'total'             => $item->subtotal,
                                        'reference_type'    => 'purchase_void',
                                        'reference_id'      => $record->id,
                                        'notes'             => 'Anulación de compra ' . $record->number,
                                        'date'              => now(),
                                    ]);
                                    $item->inventoryItem->decrement('stock_actual', $stockQty);
                                }

                                if ($record->journalEntry) {
                                    $record->journalEntry->update(['status' => 'anulado']);
                                }

                                $record->update(['status' => 'anulado']);
                            });

                            Notification::make()
                                ->title('Compra anulada correctamente')
                                ->success()
                                ->send();
                        }),
                    Action::make('verAsiento')
                        ->label('Ver Asiento')
                        ->icon('heroicon-o-document-text')
                        ->visible(fn (Purchase $record) => $record->journal_entry_id !== null)
                        ->url(fn (Purchase $record) => $record->journal_entry_id
                            ? JournalEntryResource::getUrl('edit', [
                                'record' => $record->journal_entry_id,
                                'tenant' => Filament::getTenant(),
                            ])
                            : null
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePurchases::route('/'),
        ];
    }
}
