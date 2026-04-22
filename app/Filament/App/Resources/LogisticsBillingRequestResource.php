<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LogisticsBillingRequestResource\Pages;
use App\Models\LogisticsBillingRequest;
use App\Models\StoreCustomerCompany;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LogisticsBillingRequestResource extends Resource
{
    protected static ?string $model              = LogisticsBillingRequest::class;
    protected static ?string $tenantRelationshipName = 'logisticsBillingRequests';
    protected static ?string $navigationIcon     = 'heroicon-o-document-text';
    protected static ?string $navigationLabel    = 'Órdenes por cobrar';
    protected static ?string $navigationGroup    = 'Ventas';
    protected static ?string $modelLabel         = 'Orden de cobro';
    protected static ?string $pluralModelLabel   = 'Órdenes por cobrar';
    protected static ?int    $navigationSort     = 25;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Nota de venta')->columns(2)->schema([
                \Filament\Forms\Components\TextInput::make('numero_nota_venta')
                    ->label('N.° Nota de venta')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('numero_factura')
                    ->label('N.° Factura')
                    ->disabled()
                    ->placeholder('—'),
                \Filament\Forms\Components\TextInput::make('estado')
                    ->label('Estado')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('accepted_channel')
                    ->label('Canal de aceptación')
                    ->disabled(),
            ]),
            Section::make('Valores')->columns(4)->schema([
                \Filament\Forms\Components\TextInput::make('subtotal_0')
                    ->label('Subtotal 0%')
                    ->prefix('$')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('subtotal_15')
                    ->label('Subtotal 15%')
                    ->prefix('$')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('iva')
                    ->label('IVA 15%')
                    ->prefix('$')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('total')
                    ->label('Total')
                    ->prefix('$')
                    ->disabled()
                    ->extraAttributes(['style' => 'font-weight:bold']),
                \Filament\Forms\Components\TextInput::make('descuento_monto')
                    ->label('Descuento aplicado')
                    ->prefix('$')
                    ->disabled()
                    ->placeholder('0.00')
                    ->visible(fn ($record) => $record && (float) $record->descuento_monto > 0),
                \Filament\Forms\Components\TextInput::make('descuento_tipo')
                    ->label('Tipo de descuento')
                    ->disabled()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cliente_fijo' => 'Cliente fijo',
                        'promocion'    => 'Promoción',
                        'otro'         => 'Otro',
                        default        => $state,
                    })
                    ->visible(fn ($record) => $record && (float) $record->descuento_monto > 0),
                \Filament\Forms\Components\TextInput::make('descuento_descripcion')
                    ->label('Descripción del descuento')
                    ->disabled()
                    ->placeholder('—')
                    ->columnSpan(2)
                    ->visible(fn ($record) => $record && (float) $record->descuento_monto > 0),
            ]),
            Section::make('Datos de facturación')->columns(2)->schema([
                \Filament\Forms\Components\TextInput::make('billing_nombre')
                    ->label('Nombre / Razón social')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('billing_ruc')
                    ->label('RUC / Cédula')
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('billing_direccion')
                    ->label('Dirección')
                    ->disabled(),
            ]),
            Section::make('Notas')->schema([
                Textarea::make('notas')
                    ->label('Notas internas')
                    ->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_nota_venta')
                    ->label('N.° Nota')
                    ->searchable()
                    ->fontFamily('mono')
                    ->sortable(),

                TextColumn::make('numero_factura')
                    ->label('N.° Factura')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('package.numero_tracking')
                    ->label('Tracking')
                    ->searchable()
                    ->fontFamily('mono')
                    ->placeholder('—'),

                TextColumn::make('storeCustomer.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($state, $record) =>
                        trim(($record->storeCustomer->nombre ?? '') . ' ' . ($record->storeCustomer->apellido ?? ''))
                    )
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('storeCustomer', fn ($q) =>
                            $q->withoutGlobalScopes()
                              ->where('nombre', 'like', "%{$search}%")
                              ->orWhere('apellido', 'like', "%{$search}%")
                        )
                    ),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pendiente'  => 'warning',
                        'aceptado'   => 'success',
                        'rechazado'  => 'danger',
                        'facturado'  => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) =>
                        LogisticsBillingRequest::ESTADOS[$state]['label'] ?? $state
                    ),

                TextColumn::make('billing_nombre')
                    ->label('Facturar a')
                    ->placeholder('Pendiente')
                    ->toggleable(),

                TextColumn::make('billing_ruc')
                    ->label('RUC / CI')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('accepted_at')
                    ->label('Aceptado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Emitido')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(collect(LogisticsBillingRequest::ESTADOS)
                        ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])),
            ])
            ->actions([
                ActionGroup::make([
                    // Aceptar desde ERP (sin esperar al cliente)
                    Action::make('aceptar_erp')
                        ->label('Aceptar (ERP)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->estado === 'pendiente')
                        ->form([
                            Select::make('billing_type')
                                ->label('¿A nombre de quién se factura?')
                                ->options(function ($record) {
                                    $opts = ['customer' => 'A nombre del cliente (' . trim(($record->storeCustomer->nombre ?? '') . ' ' . ($record->storeCustomer->apellido ?? '')) . ')'];
                                    $companies = StoreCustomerCompany::where('store_customer_id', $record->store_customer_id)
                                        ->where('empresa_id', Filament::getTenant()->id)
                                        ->get();
                                    foreach ($companies as $c) {
                                        $opts['company_' . $c->id] = 'Empresa: ' . $c->nombre . ' (RUC ' . $c->ruc . ')';
                                    }
                                    return $opts;
                                })
                                ->required(),
                            Textarea::make('notas')
                                ->label('Notas (opcional)')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $billingType = $data['billing_type'];
                            $company     = null;

                            if (str_starts_with($billingType, 'company_')) {
                                $companyId = (int) str_replace('company_', '', $billingType);
                                $company   = StoreCustomerCompany::find($companyId);
                                $billingType = 'company';
                            }

                            $record->aceptar('erp', $billingType, $company, $record->storeCustomer);

                            if ($data['notas'] ?? null) {
                                $record->update(['notas' => $data['notas']]);
                            }

                            Notification::make()->title('Solicitud aceptada')->success()->send();
                        }),

                    // Aplicar descuento
                    Action::make('aplicar_descuento')
                        ->label('Aplicar descuento')
                        ->icon('heroicon-o-tag')
                        ->color('warning')
                        ->visible(fn ($record) => in_array($record->estado, ['pendiente', 'aceptado']))
                        ->form(function ($record) {
                            return [
                                Select::make('tipo')
                                    ->label('Tipo de descuento')
                                    ->options([
                                        'cliente_fijo' => 'Cliente fijo',
                                        'promocion'    => 'Promoción',
                                        'otro'         => 'Otro',
                                    ])
                                    ->required()
                                    ->live(),
                                TextInput::make('descripcion')
                                    ->label('Descripción')
                                    ->placeholder('Ej. 10% descuento cliente frecuente')
                                    ->visible(fn (Get $get) => filled($get('tipo')))
                                    ->nullable(),
                                TextInput::make('monto')
                                    ->label('Monto del descuento ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required()
                                    ->helperText('Total actual: $' . number_format((float) $record->total, 2)),
                            ];
                        })
                        ->action(function ($record, array $data) {
                            $monto = (float) $data['monto'];
                            if ($monto <= 0 || $monto > (float) $record->subtotal_15) {
                                Notification::make()
                                    ->title('Monto inválido')
                                    ->body('El descuento no puede ser mayor al subtotal gravado.')
                                    ->danger()->send();
                                return;
                            }
                            $record->aplicarDescuento($data['tipo'], $monto, $data['descripcion'] ?? null);
                            Notification::make()->title('Descuento aplicado')->success()->send();
                        }),

                    // Marcar como facturado
                    Action::make('marcar_facturado')
                        ->label('Marcar como facturado')
                        ->icon('heroicon-o-document-check')
                        ->color('info')
                        ->visible(fn ($record) => $record->estado === 'aceptado')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $numero = $record->asignarNumeroFactura();
                            $record->update(['estado' => 'facturado']);
                            Notification::make()
                                ->title('Facturado: ' . $numero)
                                ->success()->send();
                        }),

                    // Rechazar
                    Action::make('rechazar')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->estado === 'pendiente')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('notas')->label('Motivo del rechazo')->rows(2)->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update(['estado' => 'rechazado', 'notas' => $data['notas']]);
                            Notification::make()->title('Solicitud rechazada')->danger()->send();
                        }),

                    // Ver nota de venta / tracking
                    Action::make('ver_paquete')
                        ->label('Ver paquete')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn ($record) => $record->package_id
                            ? route('filament.logistics.resources.packages.edit', [
                                'tenant' => Filament::getTenant()->slug,
                                'record' => $record->package_id,
                            ])
                            : null
                        )
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogisticsBillingRequests::route('/'),
        ];
    }
}
