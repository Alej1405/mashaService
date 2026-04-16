<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LogisticsPaymentClaimResource\Pages;
use App\Models\LogisticsPackage;
use App\Models\LogisticsPaymentClaim;
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
            \Illuminate\Support\Facades\DB::transaction(function () use ($claim, $notas) {
                // 1. Generar asiento contable
                $service = new AccountingService();
                $entry   = $service->generarAsientoCobroLogistico($claim);

                // 2. Actualizar estado del cobro
                $claim->update([
                    'estado'            => 'verificado',
                    'notas_verificador' => $notas,
                    'journal_entry_id'  => $entry->id,
                    'verificado_por'    => Auth::id(),
                    'verificado_at'     => now(),
                ]);

                // 3. Mover las cargas a "Coordinación de Entrega"
                LogisticsPackage::withoutGlobalScopes()
                    ->whereIn('id', $claim->package_ids ?? [])
                    ->update([
                        'estado'            => 'en_entrega',
                        'estado_secundario' => null,
                    ]);
            });

            Notification::make()
                ->title('Pago verificado — asiento contable generado')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Error verificando cobro logístico', [
                'claim_id' => $claim->id,
                'error'    => $e->getMessage(),
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
