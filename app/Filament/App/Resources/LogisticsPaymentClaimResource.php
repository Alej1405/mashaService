<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LogisticsPaymentClaimResource\Pages;
use App\Models\Customer;
use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsPackage;
use App\Models\LogisticsPaymentClaim;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\AccountingService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogisticsPaymentClaimResource extends Resource
{
    protected static ?string $model                   = LogisticsBillingRequest::class;
    protected static ?string $tenantRelationshipName  = 'logisticsBillingRequests';
    protected static ?string $navigationIcon          = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel    = 'Verificar Cobros';
    protected static ?string $navigationGroup    = 'Ventas';
    protected static ?string $modelLabel         = 'Cobro';
    protected static ?string $pluralModelLabel   = 'Cobros logísticos';
    protected static ?int    $navigationSort     = 99;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('estado', ['facturado', 'cobrado']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('numero_nota_venta')
                    ->label('N.° Nota')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('storeCustomer.nombre_completo')
                    ->label('Cliente')
                    ->searchable(['store_customers.nombre', 'store_customers.razon_social'])
                    ->sortable(),

                TextColumn::make('billing_nombre')
                    ->label('Facturar a')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => LogisticsBillingRequest::ESTADOS[$state]['color'] ?? 'gray')
                    ->formatStateUsing(fn ($state) => LogisticsBillingRequest::ESTADOS[$state]['label'] ?? $state),

                TextColumn::make('accepted_at')
                    ->label('Aceptado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('verificado_at')
                    ->label('Verificado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->options([
                        'facturado' => 'Por cobrar',
                        'cobrado'   => 'Cobrado',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver detalle')
                    ->modalHeading(fn ($record) => $record->numero_nota_venta . ' — ' . $record->storeCustomer?->nombre_completo)
                    ->modalContent(fn ($record) => view('filament.app.modals.billing-claim-detail', ['billing' => $record, 'claim' => static::getClaim($record)])),

                Action::make('verificar')
                    ->label('Verificar cobro')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->estado === 'facturado')
                    ->requiresConfirmation()
                    ->modalHeading('Verificar cobro')
                    ->modalDescription('Al verificar, se generará la venta y el asiento contable.')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('monto_verificado')
                            ->label('Monto verificado ($)')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->default(fn ($record) => $record->total > 0 ? $record->total : null)
                            ->helperText('Confirma o corrige el monto realmente recibido.'),
                        Textarea::make('notas_verificador')
                            ->label('Notas del verificador (opcional)')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        static::verificarCobro($record, (float) $data['monto_verificado'], $data['notas_verificador'] ?? null);
                    }),
            ])
            ->bulkActions([]);
    }

    /** Devuelve el último LogisticsPaymentClaim del cliente para este paquete */
    public static function getClaim(LogisticsBillingRequest $billing): ?LogisticsPaymentClaim
    {
        return LogisticsPaymentClaim::withoutGlobalScopes()
            ->where('empresa_id', $billing->empresa_id)
            ->where('store_customer_id', $billing->store_customer_id)
            ->whereJsonContains('package_ids', $billing->package_id)
            ->latest()
            ->first();
    }

    private static function verificarCobro(LogisticsBillingRequest $billing, float $montoVerificado, ?string $notas): void
    {
        try {
            DB::transaction(function () use ($billing, $montoVerificado, $notas) {
                $empresaId     = $billing->empresa_id;
                $billingNombre = $billing->billing_nombre ?? $billing->storeCustomer?->nombre_completo ?? 'Cliente';
                $billingRuc    = $billing->billing_ruc    ?? $billing->storeCustomer?->cedula_ruc     ?? null;

                // ── 1. Encontrar o crear Customer ERP ────────────────────────────
                $customer = null;
                if ($billingRuc) {
                    $customer = Customer::withoutGlobalScopes()
                        ->where('empresa_id', $empresaId)
                        ->where('numero_identificacion', $billingRuc)
                        ->first();
                }
                if (! $customer) {
                    $customer = Customer::create([
                        'empresa_id'            => $empresaId,
                        'nombre'                => $billingNombre,
                        'tipo_persona'          => 'juridica',
                        'tipo_identificacion'   => strlen($billingRuc ?? '') === 13 ? 'ruc' : 'cedula',
                        'numero_identificacion' => $billingRuc ?? '9999999999999',
                        'activo'                => true,
                    ]);
                }

                // ── 2. Construir ítems de la venta desde billing request ──────────
                $saleItemsData = [];
                foreach ($billing->items ?? [] as $item) {
                    $saleItemsData[] = [
                        'descripcion_servicio' => $item['descripcion'],
                        'tipo_item'            => 'servicio',
                        'cantidad'             => (float) $item['cantidad'],
                        'precio_unitario'      => (float) $item['precio'],
                        'aplica_iva'           => ($item['iva_pct'] ?? 0) > 0,
                    ];
                }

                // Fallback si no hay items
                if (empty($saleItemsData)) {
                    $saleItemsData[] = [
                        'descripcion_servicio' => 'Servicios logísticos de importación',
                        'tipo_item'            => 'servicio',
                        'cantidad'             => 1,
                        'precio_unitario'      => $montoVerificado,
                        'aplica_iva'           => true,
                    ];
                }

                // ── 3. Crear la venta ─────────────────────────────────────────────
                $sale = Sale::create([
                    'empresa_id'     => $empresaId,
                    'customer_id'    => $customer->id,
                    'fecha'          => now()->toDateString(),
                    'tipo_venta'     => 'contado',
                    'tipo_operacion' => 'servicios',
                    'forma_pago'     => 'transferencia',
                    'estado'         => 'confirmado',
                    'confirmado_por' => Auth::id(),
                    'confirmado_at'  => now(),
                    'notas'          => 'Generada automáticamente al verificar cobro ' . $billing->numero_nota_venta
                                       . ($notas ? "\n" . $notas : ''),
                ]);

                foreach ($saleItemsData as $itemData) {
                    SaleItem::create(array_merge($itemData, ['sale_id' => $sale->id]));
                }

                $sale->refresh();

                // ── 4. Generar asiento contable ───────────────────────────────────
                $service = new AccountingService();
                $entry   = $service->generarAsientoVenta($sale);

                $sale->update(['journal_entry_id' => $entry->id]);

                // ── 5. Actualizar billing request ─────────────────────────────────
                $billing->update([
                    'estado'        => 'cobrado',
                    'sale_id'       => $sale->id,
                    'verificado_por' => Auth::id(),
                    'verificado_at'  => now(),
                    'notas'         => $billing->notas . ($notas ? "\n[Verificador] " . $notas : ''),
                ]);

                // ── 6. Mover la carga a "Coordinación de Entrega" ─────────────────
                LogisticsPackage::withoutGlobalScopes()
                    ->where('id', $billing->package_id)
                    ->update([
                        'estado'            => 'en_entrega',
                        'estado_secundario' => null,
                        'sale_id'           => $sale->id,
                    ]);
            });

            Notification::make()
                ->title('Pago verificado — venta y asiento contable generados')
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Log::error('Error verificando cobro logístico', [
                'billing_id' => $billing->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error al verificar: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogisticsPaymentClaims::route('/'),
        ];
    }
}
