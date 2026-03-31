<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\InventoryItem;
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
use Filament\Support\Enums\Alignment;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryMovement;
use App\Filament\App\Resources\BankAccountResource;
use App\Filament\App\Resources\CashRegisterResource;
use App\Filament\App\Resources\CreditCardResource;
use App\Filament\App\Resources\CustomerResource;
use App\Filament\App\Resources\InventoryItemResource;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $tenantRelationshipName = 'sales';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('subtotal')->default(0)->live(),
                Forms\Components\Hidden::make('iva')->default(0)->live(),
                Forms\Components\Hidden::make('total')->default(0)->live(),

                Wizard::make([
                    Step::make('Cliente y Condiciones')
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->label('Cliente')
                                ->relationship('customer', 'nombre')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Customer $record) => "{$record->nombre} - {$record->numero_identificacion}")
                                ->required()
                                ->preload()
                                ->createOptionForm(fn () => CustomerResource::getQuickCreateFormSchema())
                                ->createOptionUsing(function (array $data): int {
                                    return \App\Models\Customer::create([
                                        ...$data,
                                        'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                        'activo'     => true,
                                    ])->getKey();
                                }),
                            Forms\Components\DatePicker::make('fecha')
                                ->label('Fecha de Emisión')
                                ->default(now())
                                ->required(),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('tipo_venta')
                                        ->label('Tipo de Venta')
                                        ->options([
                                            'contado' => 'Contado',
                                            'credito' => 'Crédito',
                                        ])
                                        ->default('contado')
                                        ->required()
                                        ->reactive(),
                                    Forms\Components\DatePicker::make('fecha_vencimiento')
                                        ->label('Vencimiento')
                                        ->visible(fn (callable $get) => $get('tipo_venta') === 'credito')
                                        ->required(fn (callable $get) => $get('tipo_venta') === 'credito'),
                                    Forms\Components\Select::make('tipo_operacion')
                                        ->label('Tipo Operación')
                                        ->options([
                                            'productos' => 'Productos',
                                            'servicios' => 'Servicios',
                                            'mixta' => 'Mixta',
                                            'exportacion' => 'Exportación',
                                        ])
                                        ->default('productos')
                                        ->required()
                                        ->reactive(),
                                    Forms\Components\Select::make('forma_pago')
                                        ->label('Forma de Pago')
                                        ->options([
                                            'efectivo'      => 'Efectivo',
                                            'transferencia' => 'Transferencia Bancaria',
                                            'cheque'        => 'Cheque',
                                            'tarjeta'       => 'Tarjeta de Crédito/Débito',
                                            'credito'       => 'Crédito Directo',
                                        ])
                                        ->default('efectivo')
                                        ->required()
                                        ->reactive(),

                                    Forms\Components\Select::make('cash_register_id')
                                        ->label('Caja')
                                        ->relationship('cashRegister', 'nombre', fn($query) => $query->where('activo', true))
                                        ->visible(fn ($get) => $get('forma_pago') === 'efectivo')
                                        ->required(fn ($get) => $get('forma_pago') === 'efectivo')
                                        ->searchable()
                                        ->preload()
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
                                        ->visible(fn(callable $get) =>
                                            in_array($get('forma_pago'), ['transferencia', 'cheque'])
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->createOptionModalHeading('Nueva Cuenta Bancaria')
                                        ->createOptionForm(fn () => BankAccountResource::getQuickCreateFormSchema())
                                        ->createOptionUsing(function (array $data): int {
                                            return \App\Models\BankAccount::create([
                                                ...$data,
                                                'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                                'activo'     => true,
                                            ])->getKey();
                                        }),

                                    Forms\Components\Select::make('credit_card_id')
                                        ->label('Tarjeta de Crédito')
                                        ->relationship('creditCard', 'nombre', fn($query) => $query->where('activo', true))
                                        ->visible(fn ($get) => $get('forma_pago') === 'tarjeta')
                                        ->required(fn ($get) => $get('forma_pago') === 'tarjeta')
                                        ->searchable()
                                        ->preload()
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
                                ]),
                            Forms\Components\Textarea::make('notas')
                                ->label('Observaciones Internas')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Step::make('Productos / Servicios')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->live()
                                ->schema([
                                    Forms\Components\Select::make('inventory_item_id')
                                        ->label('Producto / Insumo / Materia Prima')
                                        ->options(function() {
                                            $empresaId = \Filament\Facades\Filament::getTenant()->id;

                                            return \App\Models\InventoryItem::where('empresa_id', $empresaId)
                                                ->where('activo', true)
                                                ->where('stock_actual', '>', 0)
                                                ->orderBy('type')
                                                ->orderBy('nombre')
                                                ->get()
                                                ->groupBy('type')
                                                ->mapWithKeys(fn($items, $type) => [
                                                    ucfirst(str_replace('_', ' ', $type)) => $items->mapWithKeys(
                                                        fn($item) => [
                                                            $item->id => $item->nombre .
                                                                ' (Stock: ' . $item->stock_actual .
                                                                ' ' . $item->unidad . ')'
                                                        ]
                                                    )
                                                ]);
                                        })
                                        ->searchable()
                                        ->required()
                                        ->reactive()
                                        ->createOptionModalHeading('Nuevo Ítem de Inventario')
                                        ->createOptionForm(fn () => InventoryItemResource::getQuickCreateFormSchema())
                                        ->createOptionUsing(function (array $data): int {
                                            return \App\Models\InventoryItem::create([
                                                ...$data,
                                                'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                                'activo'     => true,
                                            ])->getKey();
                                        })
                                        ->afterStateUpdated(function($state, callable $set) {
                                            if (!$state) return;
                                            $item = \App\Models\InventoryItem::find($state);
                                            if ($item) {
                                                $set('precio_unitario', $item->precio_venta ?? $item->sale_price ?? 0);
                                                $set('tipo_item', $item->type);
                                                $set('aplica_iva', $item->aplica_iva ?? true);
                                            }
                                        }),

                                    \Filament\Forms\Components\Hidden::make('tipo_item')
                                        ->required(),

                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\TextInput::make('cantidad')
                                                ->label('Cantidad')
                                                ->numeric()
                                                ->default(1)
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                                    self::updateTotals($livewire, $set);
                                                }),
                                            Forms\Components\TextInput::make('precio_unitario')
                                                ->label('P. Unitario')
                                                ->numeric()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                                    self::updateTotals($livewire, $set);
                                                }),
                                            Forms\Components\Toggle::make('aplica_iva')
                                                ->label('Aplica IVA (15%)')
                                                ->default(true)
                                                ->live()
                                                ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                                    self::updateTotals($livewire, $set);
                                                }),
                                            Forms\Components\Placeholder::make('total_linea_display')
                                                ->label('Subtotal Línea')
                                                ->content(function (callable $get) {
                                                    $qty = (float) $get('cantidad');
                                                    $price = (float) $get('precio_unitario');
                                                    return '$ ' . number_format($qty * $price, 2);
                                                }),
                                        ]),

                                ])
                                ->columns(1)
                                ->defaultItems(1)
                                ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                    self::updateTotals($livewire, $set);
                                }),
                        ]),

                    Step::make('Resumen y Totales')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('subtotal_general')
                                        ->label('Subtotal 0% / Base')
                                        ->content(fn ($get) => '$ ' . number_format((float)$get('subtotal'), 2)),
                                    Forms\Components\Placeholder::make('iva_total')
                                        ->label('IVA 15%')
                                        ->content(fn ($get) => '$ ' . number_format((float)$get('iva'), 2)),
                                    Forms\Components\Placeholder::make('total_final')
                                        ->label('TOTAL A PAGAR')
                                        ->extraAttributes(['class' => 'text-xl font-bold text-primary-600'])
                                        ->content(fn ($get) => '$ ' . number_format((float)$get('total'), 2)),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-color-primary fi-btn-color-primary fi-ac-btn-action" style="background-color: #16a34a; color: white; padding: 0.6rem 1.2rem; border-radius: 0.5rem; font-weight: bold; border: none; cursor: pointer;">Guardar Venta</button>')),
            ]);
    }

    public static function updateTotals($livewire, $set)
    {
        $items = $livewire->data['items'] ?? [];
        $subtotal = 0;
        $iva = 0;

        foreach ($items as $item) {
            $s = ((float) ($item['cantidad'] ?? 0)) * ((float) ($item['precio_unitario'] ?? 0));
            $subtotal += $s;
            if ($item['aplica_iva'] ?? false) {
                // IVA 15% fijo para Ecuador
                $iva += $s * 0.15;
            }
        }

        $set('subtotal', $subtotal);
        $set('iva', $iva);
        $set('total', $subtotal + $iva);

        if (isset($livewire->data) && is_array($livewire->data)) {
            $livewire->data['subtotal'] = $subtotal;
            $livewire->data['iva'] = $iva;
            $livewire->data['total'] = $subtotal + $iva;
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referencia')
                    ->label('Referencia')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total Venta')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'borrador' => 'gray',
                        'confirmado' => 'success',
                        'anulado' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'confirmado' => 'Confirmado',
                        'anulado' => 'Anulado',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_venta')
                    ->label('Tipo de Pago')
                    ->options([
                        'contado' => 'Contado',
                        'credito' => 'Crédito',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Action::make('confirmar')
                        ->label('Confirmar Venta')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Sale $record) => $record->estado === 'borrador')
                        ->requiresConfirmation()
                        ->action(function (Sale $record) {
                            $record->update(['estado' => 'confirmado']);
                            
                            Notification::make()
                                ->title('Venta confirmada y asiento generado')
                                ->success()
                                ->send();
                        }),
                    Action::make('anular')
                        ->label('Anular Venta')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Sale $record) => $record->estado === 'confirmado')
                        ->requiresConfirmation()
                        ->action(function (Sale $record) {
                            DB::transaction(function () use ($record) {
                                // Revertir inventario si aplica
                                foreach ($record->items as $item) {
                                    if ($item->tipo_item === 'producto' && $item->inventory_item_id) {
                                        InventoryMovement::create([
                                            'empresa_id' => $record->empresa_id,
                                            'inventory_item_id' => $item->inventory_item_id,
                                            'type' => 'entrada',
                                            'quantity' => $item->cantidad,
                                            'unit_price' => $item->inventoryItem->purchase_price ?? 0,
                                            'total' => $item->cantidad * ($item->inventoryItem->purchase_price ?? 0),
                                            'reference_type' => 'sale_annulment',
                                            'reference_id' => $record->id,
                                            'notes' => 'Anulación de venta ' . $record->referencia,
                                            'date' => now(),
                                        ]);
                                        $item->inventoryItem->increment('stock_actual', $item->cantidad);
                                    }
                                }

                                if ($record->journalEntry) {
                                    $record->journalEntry->update(['status' => 'anulado']);
                                }

                                $record->update(['estado' => 'anulado']);
                            });

                            Notification::make()
                                ->title('Venta anulada correctamente')
                                ->warning()
                                ->send();
                        }),
                    Action::make('verAsiento')
                        ->label('Ver Asiento')
                        ->icon('heroicon-o-document-text')
                        ->visible(fn (Sale $record) => $record->journal_entry_id !== null)
                        ->url(fn (Sale $record) => JournalEntryResource::getUrl('edit', [
                            'record' => $record->journal_entry_id,
                            'tenant' => Filament::getTenant(),
                        ])),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
