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
use App\Filament\App\Resources\SupplierResource;
use App\Filament\App\Resources\InventoryItemResource;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

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
                    Step::make('Proveedor')
                        ->schema([
                            Forms\Components\Select::make('supplier_id')
                                ->label('Proveedor')
                                ->relationship('supplier', 'nombre')
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(fn (Supplier $record) => "{$record->nombre} - {$record->numero_identificacion}")
                                ->required()
                                ->createOptionForm(fn () => SupplierResource::getQuickCreateFormSchema()),
                            Forms\Components\DatePicker::make('date')
                                ->label('Fecha')
                                ->default(now())
                                ->required(),
                            Forms\Components\Select::make('forma_pago')
                                ->label('Forma de Pago')
                                ->options([
                                    'efectivo' => 'Efectivo',
                                    'transferencia' => 'Transferencia/Débito',
                                    'tarjeta_credito' => 'Tarjeta de Crédito',
                                    'credito' => 'Crédito (Cuentas por Pagar)',
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
                                ->preload(),
                            Forms\Components\Select::make('bank_account_id')
                                ->label('Cuenta Bancaria')
                                ->relationship('bankAccount', 'numero_cuenta', fn($query) => $query->where('activo', true))
                                ->visible(fn ($get) => $get('forma_pago') === 'transferencia')
                                ->required(fn ($get) => $get('forma_pago') === 'transferencia')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('credit_card_id')
                                ->label('Tarjeta de Crédito')
                                ->relationship('creditCard', 'nombre', fn($query) => $query->where('activo', true))
                                ->visible(fn ($get) => $get('forma_pago') === 'tarjeta_credito')
                                ->required(fn ($get) => $get('forma_pago') === 'tarjeta_credito')
                                ->searchable()
                                ->preload(),
                            Forms\Components\DatePicker::make('fecha_vencimiento')
                                ->label('Fecha de Vencimiento')
                                ->visible(fn (callable $get) => $get('forma_pago') === 'credito')
                                ->required(fn (callable $get) => $get('forma_pago') === 'credito'),
                            Forms\Components\Textarea::make('notas')
                                ->label('Notas')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Step::make('Productos')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->live()
                                ->schema([
                                    Forms\Components\Select::make('inventory_item_id')
                                        ->label('Producto/Insumo')
                                        ->relationship('inventoryItem', 'nombre')
                                        ->searchable()
                                        ->getOptionLabelFromRecordUsing(fn (InventoryItem $record) => "{$record->codigo} - {$record->nombre} (Stock: {$record->stock_actual})")
                                        ->required()
                                        ->createOptionForm(fn () => InventoryItemResource::getQuickCreateFormSchema())
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $item = InventoryItem::find($state);
                                            if ($item) {
                                                $set('unit_price', $item->purchase_price ?? 0);
                                            }
                                        }),
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(0.0001)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                            self::updateTotals($livewire, $set);
                                        }),
                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('Precio Unitario')
                                        ->numeric()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                            self::updateTotals($livewire, $set);
                                        }),
                                    Forms\Components\Toggle::make('aplica_iva')
                                        ->label('Aplica IVA')
                                        ->default(true)
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                            self::updateTotals($livewire, $set);
                                        }),
                                    Forms\Components\Placeholder::make('subtotal_display')
                                        ->label('Subtotal')
                                        ->content(function (callable $get) {
                                            $qty = (float) $get('quantity');
                                            $price = (float) $get('unit_price');
                                            return number_format($qty * $price, 4);
                                        }),
                                    Forms\Components\Placeholder::make('iva_display')
                                        ->label('IVA 15%')
                                        ->content(function (callable $get) {
                                            $qty = (float) $get('quantity');
                                            $price = (float) $get('unit_price');
                                            $aplica = $get('aplica_iva');
                                            return number_format($aplica ? ($qty * $price * 0.15) : 0, 4);
                                        }),
                                    Forms\Components\Placeholder::make('total_item_display')
                                        ->label('Total Item')
                                        ->content(function (callable $get) {
                                            $qty = (float) $get('quantity');
                                            $price = (float) $get('unit_price');
                                            $aplica = $get('aplica_iva');
                                            $sub = $qty * $price;
                                            $iva = $aplica ? $sub * 0.15 : 0;
                                            return number_format($sub + $iva, 4);
                                        }),
                                ])
                                ->columns(4)
                                ->defaultItems(1)
                                ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, callable $set) {
                                    self::updateTotals($livewire, $set);
                                }),
                        ]),

                    Step::make('Resumen')
                        ->schema([
                            Forms\Components\Placeholder::make('resumen_compra')
                                ->content(fn ($get) => view('filament.forms.components.purchase-summary', ['get' => $get])),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make('subtotal_general')
                                        ->label('Subtotal General')
                                        ->content(function ($get) {
                                            $items = $get('items') ?? [];
                                            $sum = collect($items)->sum(fn($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0));
                                            return number_format($sum, 4);
                                        }),
                                    Forms\Components\Placeholder::make('iva_total')
                                        ->label('IVA Total')
                                        ->content(function ($get) {
                                            $items = $get('items') ?? [];
                                            $sum = collect($items)->sum(fn($i) => ($i['aplica_iva'] ?? false) ? (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0) * 0.15 : 0);
                                            return number_format($sum, 4);
                                        }),
                                    Forms\Components\Placeholder::make('total_general')
                                        ->label('Total General')
                                        ->content(function ($get) {
                                            $items = $get('items') ?? [];
                                            $total = 0;
                                            foreach($items as $i) {
                                                $sub = (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0);
                                                $total += $sub + (($i['aplica_iva'] ?? false) ? $sub * 0.15 : 0);
                                            }
                                            return number_format($total, 4);
                                        }),
                                ]),
                        ]),
                ])
                ->columnSpanFull()
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-color-primary fi-btn-color-primary fi-ac-btn-action" style="background-color: orange; color: white; padding: 0.6rem 1.2rem; border-radius: 0.5rem; font-weight: bold; border: none; cursor: pointer;">Guardar Compra</button>')),
            ]);
    }

    public static function updateTotals($livewire, $set)
    {
        $items = $livewire->data['items'] ?? [];
        $subtotal = 0;
        $iva = 0;

        foreach ($items as $item) {
            $s = ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0));
            $subtotal += $s;
            if ($item['aplica_iva'] ?? false) {
                $iva += $s * 0.15;
            }
        }

        $set('subtotal', $subtotal);
        $set('iva', $iva);
        $set('total', $subtotal + $iva);

        // Seguridad: Asegurar que el estado raíz de Livewire se actualice
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
                Tables\Columns\TextColumn::make('number')
                    ->label('Referencia')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_pago')
                    ->label('Tipo Pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contado' => 'success',
                        'credito_local' => 'warning',
                        'credito_exterior' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'borrador' => 'gray',
                        'confirmado' => 'success',
                        'anulado' => 'danger',
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
                            // RECALCULAR TOTALES ANTES DE CONFIRMAR (Seguridad)
                            $subtotal = $record->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_price);
                            $iva = $record->items->sum(fn($i) => $i->aplica_iva ? (float)$i->quantity * (float)$i->unit_price * 0.15 : 0);
                            
                            $record->update([
                                'status' => 'confirmado',
                                'subtotal' => $subtotal,
                                'iva' => $iva,
                                'total' => $subtotal + $iva,
                            ]);

                            Notification::make()
                                ->title('Compra confirmada exitosamente')
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
                                    InventoryMovement::create([
                                        'empresa_id' => $record->empresa_id,
                                        'inventory_item_id' => $item->inventory_item_id,
                                        'type' => 'salida',
                                        'quantity' => $item->quantity,
                                        'unit_price' => $item->unit_price,
                                        'total' => $item->subtotal,
                                        'reference_type' => 'purchase_void',
                                        'reference_id' => $record->id,
                                        'notes' => 'Anulación de compra ' . $record->number,
                                        'date' => now(),
                                    ]);
                                    $item->inventoryItem->decrement('stock_actual', $item->quantity);
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
