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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LogisticsPaymentClaimResource extends Resource
{
    protected static ?string $model              = LogisticsPaymentClaim::class;
    protected static ?string $navigationIcon     = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel    = 'Verificar Cobros';
    protected static ?string $navigationGroup    = 'Ventas';
    protected static ?string $modelLabel         = 'Cobro';
    protected static ?string $pluralModelLabel   = 'Cobros logísticos';
    protected static ?int    $navigationSort     = 99;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
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
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('storeCustomer.nombre_completo')
                    ->label('Cliente')
                    ->searchable(['store_customers.nombre', 'store_customers.razon_social'])
                    ->sortable(),

                TextColumn::make('monto_declarado')
                    ->label('Monto declarado')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('package_ids')
                    ->label('Paquetes')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' paquete(s)' : '—')
                    ->alignCenter(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => LogisticsPaymentClaim::ESTADOS[$state]['color'] ?? 'gray')
                    ->formatStateUsing(fn ($state) => LogisticsPaymentClaim::ESTADOS[$state]['label'] ?? $state),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('verificado_at')
                    ->label('Verificado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->options(collect(LogisticsPaymentClaim::ESTADOS)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver detalle')
                    ->modalHeading(fn ($record) => 'Cobro #' . $record->id . ' — ' . $record->storeCustomer?->nombre_completo)
                    ->modalContent(fn ($record) => view('filament.app.modals.payment-claim-detail', ['claim' => $record])),

                Action::make('verificar')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->modalHeading('Verificar pago')
                    ->modalDescription('Al verificar, las cargas pasarán a "Coordinación de Entrega" y se generará el asiento contable.')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('monto_verificado')
                            ->label('Monto verificado ($)')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->default(fn ($record) => $record->monto_declarado > 0 ? $record->monto_declarado : null)
                            ->helperText('Confirma o corrige el monto realmente recibido.'),
                        Textarea::make('notas_verificador')
                            ->label('Notas del verificador (opcional)')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        // Actualizar monto con el verificado antes de generar asiento
                        $record->update(['monto_declarado' => $data['monto_verificado']]);
                        static::verificarCobro($record->fresh(), $data['notas_verificador'] ?? null);
                    }),

                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('notas_verificador')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'estado'             => 'rechazado',
                            'notas_verificador'  => $data['notas_verificador'],
                            'verificado_por'     => Auth::id(),
                            'verificado_at'      => now(),
                        ]);

                        Notification::make()
                            ->title('Cobro rechazado')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    private static function verificarCobro(LogisticsPaymentClaim $claim, ?string $notas): void
    {
        try {
            DB::transaction(function () use ($claim, $notas) {
                $empresaId   = $claim->empresa_id;
                $storeCustomer = $claim->storeCustomer;
                $packageIds  = $claim->package_ids ?? [];

                // ── 1. Resolver datos de facturación desde el billing request ──────
                // Busca la billing request aceptada del primer paquete con una
                $billingRequest = LogisticsBillingRequest::whereIn('package_id', $packageIds)
                    ->whereIn('estado', ['aceptado', 'facturado'])
                    ->with('billingCompany')
                    ->latest()
                    ->first();

                $billingNombre = $billingRequest?->billing_nombre
                    ?? $storeCustomer?->nombre_completo
                    ?? 'Cliente';
                $billingRuc = $billingRequest?->billing_ruc
                    ?? $storeCustomer?->cedula_ruc
                    ?? null;

                // ── 2. Encontrar o crear Customer ERP ────────────────────────────
                $customer = null;
                if ($billingRuc) {
                    $customer = Customer::withoutGlobalScopes()
                        ->where('empresa_id', $empresaId)
                        ->where('numero_identificacion', $billingRuc)
                        ->first();
                }
                if (! $customer) {
                    $customer = Customer::create([
                        'empresa_id'             => $empresaId,
                        'nombre'                 => $billingNombre,
                        'tipo_persona'           => 'juridica',
                        'tipo_identificacion'    => strlen($billingRuc ?? '') === 13 ? 'ruc' : 'cedula',
                        'numero_identificacion'  => $billingRuc ?? '9999999999999',
                        'activo'                 => true,
                    ]);
                }

                // ── 3. Construir ítems de la venta ────────────────────────────────
                // Consolidar items de todas las billing requests de estos paquetes
                $allBillings = LogisticsBillingRequest::whereIn('package_id', $packageIds)
                    ->whereIn('estado', ['aceptado', 'facturado'])
                    ->get();

                $saleItemsData = [];
                if ($allBillings->isNotEmpty()) {
                    foreach ($allBillings as $br) {
                        foreach ($br->items as $item) {
                            $saleItemsData[] = [
                                'descripcion_servicio' => $item['descripcion'],
                                'tipo_item'            => 'servicio',
                                'cantidad'             => (float) $item['cantidad'],
                                'precio_unitario'      => (float) $item['precio'],
                                'aplica_iva'           => $item['iva_pct'] > 0,
                            ];
                        }
                    }
                }

                // Fallback: si no hay billing requests, un ítem genérico
                if (empty($saleItemsData)) {
                    $saleItemsData[] = [
                        'descripcion_servicio' => 'Servicios logísticos de importación',
                        'tipo_item'            => 'servicio',
                        'cantidad'             => 1,
                        'precio_unitario'      => (float) $claim->monto_declarado,
                        'aplica_iva'           => true,
                    ];
                }

                // ── 4. Crear la venta ─────────────────────────────────────────────
                $sale = Sale::create([
                    'empresa_id'      => $empresaId,
                    'customer_id'     => $customer->id,
                    'fecha'           => now()->toDateString(),
                    'tipo_venta'      => 'contado',
                    'tipo_operacion'  => 'servicios',
                    'forma_pago'      => 'transferencia',
                    'estado'          => 'confirmado',
                    'confirmado_por'  => Auth::id(),
                    'confirmado_at'   => now(),
                    'notas'           => 'Generada automáticamente al verificar cobro #'
                                        . $claim->id
                                        . ($notas ? "\n" . $notas : ''),
                ]);

                foreach ($saleItemsData as $itemData) {
                    SaleItem::create(array_merge($itemData, ['sale_id' => $sale->id]));
                }

                $sale->refresh();

                // ── 5. Generar asiento contable de venta ──────────────────────────
                $service = new AccountingService();
                $entry   = $service->generarAsientoVenta($sale);

                // Vincular asiento a la venta y al claim
                $sale->update(['journal_entry_id' => $entry->id]);

                // Marcar billing requests como facturadas
                LogisticsBillingRequest::whereIn('package_id', $packageIds)
                    ->whereIn('estado', ['aceptado'])
                    ->update(['estado' => 'facturado']);

                // ── 6. Actualizar estado del cobro ────────────────────────────────
                $claim->update([
                    'estado'            => 'verificado',
                    'notas_verificador' => $notas,
                    'journal_entry_id'  => $entry->id,
                    'sale_id'           => $sale->id,
                    'verificado_por'    => Auth::id(),
                    'verificado_at'     => now(),
                ]);

                // ── 7. Mover las cargas a "Coordinación de Entrega" ───────────────
                LogisticsPackage::withoutGlobalScopes()
                    ->whereIn('id', $packageIds)
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
                'claim_id' => $claim->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
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
