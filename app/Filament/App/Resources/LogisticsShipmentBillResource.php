<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LogisticsShipmentBillResource\Pages;
use App\Models\LogisticsShipment;
use App\Models\LogisticsShipmentBill;
use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LogisticsShipmentBillResource extends Resource
{
    protected static ?string $model                  = LogisticsShipmentBill::class;
    protected static ?string $tenantRelationshipName = 'logisticsShipmentBills';
    protected static ?string $navigationIcon         = 'heroicon-o-document-minus';
    protected static ?string $navigationLabel   = 'Facturas por pagar';
    protected static ?string $navigationGroup   = 'Logística';
    protected static ?string $modelLabel        = 'Factura por pagar';
    protected static ?string $pluralModelLabel  = 'Facturas por pagar';
    protected static ?int    $navigationSort    = 10;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Embarque y proveedor')->columns(2)->schema([
                Select::make('shipment_id')
                    ->label('Embarque')
                    ->options(fn () => LogisticsShipment::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->with('consignatario')
                        ->orderByDesc('created_at')
                        ->get()
                        ->mapWithKeys(fn ($s) => [
                            $s->id => implode(' — ', array_filter([
                                $s->numero_embarque,
                                $s->consignatario?->nombre,
                                $s->numero_guia_aerea,
                            ])),
                        ]))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('_liquidacion', null)),

                Select::make('supplier_id')
                    ->label('Proveedor')
                    ->options(fn () => Supplier::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->where('activo', true)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id'))
                    ->searchable()
                    ->required()
                    ->createOptionModalHeading('Nuevo proveedor')
                    ->createOptionForm([
                        TextInput::make('nombre')
                            ->label('Nombre / Razón social')
                            ->required()
                            ->maxLength(200),
                        Select::make('tipo_identificacion')
                            ->label('Tipo de identificación')
                            ->options([
                                'ruc'     => 'RUC',
                                'cedula'  => 'Cédula',
                                'pasaporte' => 'Pasaporte',
                                'exterior' => 'Identificación del exterior',
                            ])
                            ->required(),
                        TextInput::make('numero_identificacion')
                            ->label('Número de identificación')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('correo_principal')
                            ->label('Correo')
                            ->email()
                            ->nullable(),
                        TextInput::make('telefono_principal')
                            ->label('Teléfono')
                            ->nullable(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $supplier = Supplier::create(array_merge($data, [
                            'empresa_id' => Filament::getTenant()->id,
                            'activo'     => true,
                        ]));
                        return $supplier->id;
                    }),
            ]),

            Section::make('Detalle de la factura')
                ->columns(2)
                ->schema([
                    TextInput::make('descripcion')
                        ->label('Descripción')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('numero_factura_proveedor')
                        ->label('N.° Factura del proveedor')
                        ->placeholder('Ej. 001-001-000000123')
                        ->nullable(),

                    DatePicker::make('fecha_factura')
                        ->label('Fecha de factura')
                        ->nullable(),

                    // PDF visible al editar (reemplazar o descargar el existente)
                    FileUpload::make('factura_pdf_path')
                        ->label('Factura PDF')
                        ->disk('public')
                        ->directory('logistics/facturas-proveedor')
                        ->acceptedFileTypes(['application/pdf'])
                        ->downloadable()
                        ->openable()
                        ->nullable()
                        ->columnSpanFull()
                        ->visibleOn('edit')
                        ->dehydrateStateUsing(fn ($state) => is_array($state) ? (string) reset($state) : (string) $state),
                ]),

            Section::make('Valores')
                ->columns(4)
                ->schema([
                TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $sub = (float) $state;
                        $pct = (int) $get('iva_pct');
                        $iva = round($sub * $pct / 100, 2);
                        $set('iva_monto', $iva);
                        $set('total', round($sub + $iva, 2));
                    }),

                Select::make('iva_pct')
                    ->label('IVA %')
                    ->options([0 => '0%', 5 => '5%', 15 => '15%'])
                    ->default(15)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $sub = (float) $get('subtotal');
                        $iva = round($sub * (int) $state / 100, 2);
                        $set('iva_monto', $iva);
                        $set('total', round($sub + $iva, 2));
                    }),

                TextInput::make('iva_monto')
                    ->label('Monto IVA')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('total')
                    ->label('Total')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated()
                    ->extraAttributes(['style' => 'font-weight:bold']),
            ]),

            Section::make('Pago')
                ->columns(2)
                ->schema([
                Select::make('estado')
                    ->label('Estado')
                    ->options(['por_pagar' => 'Por pagar', 'pagada' => 'Pagada'])
                    ->default('por_pagar')
                    ->required()
                    ->live(),

                DatePicker::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->visible(fn (Get $get) => $get('estado') === 'pagada')
                    ->nullable(),

                Textarea::make('notas')
                    ->label('Notas')
                    ->rows(2)
                    ->columnSpanFull()
                    ->nullable(),
            ]),

            // ── Liquidación del embarque ──────────────────────────────────────
            Section::make('Liquidación del embarque')
                ->description('Resumen financiero del embarque seleccionado.')
                ->visible(fn (Get $get) => filled($get('shipment_id')))
                ->schema([
                    Placeholder::make('_liquidacion')
                        ->label('')
                        ->content(function (Get $get, $record) {
                            $shipmentId = $get('shipment_id');
                            if (! $shipmentId) {
                                return '';
                            }

                            $empresaId = Filament::getTenant()->id;

                            // Ingresos: billing requests facturadas/cobradas de los paquetes del embarque
                            $ingresos = \App\Models\LogisticsBillingRequest::withoutGlobalScopes()
                                ->where('empresa_id', $empresaId)
                                ->whereIn('estado', ['facturado', 'cobrado'])
                                ->whereHas('package.shipments', fn ($q) => $q->where('logistics_shipments.id', $shipmentId))
                                ->sum('total');

                            // Egresos guardados — excluye el registro actual al editar
                            $query = \App\Models\LogisticsShipmentBill::withoutGlobalScopes()
                                ->where('empresa_id', $empresaId)
                                ->where('shipment_id', $shipmentId);

                            if ($record?->id) {
                                $query->where('id', '!=', $record->id);
                            }

                            $egresosBD = (float) $query->sum('total');

                            // Valor que se está registrando ahora mismo en el formulario
                            $subActual = (float) $get('subtotal');
                            $pctActual = (int) $get('iva_pct');
                            $totalActual = round($subActual + round($subActual * $pctActual / 100, 2), 2);

                            $egresos  = $egresosBD + $totalActual;
                            $utilidad = $ingresos - $egresos;
                            $color    = $utilidad >= 0 ? '#22c55e' : '#ef4444';

                            return new HtmlString('
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;padding:.5rem 0">
                                    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:.5rem;padding:1rem;text-align:center">
                                        <div style="font-size:.75rem;color:#86efac;text-transform:uppercase;letter-spacing:.05em">Ingresos facturados</div>
                                        <div style="font-size:1.5rem;font-weight:700;color:#22c55e">$' . number_format($ingresos, 2) . '</div>
                                    </div>
                                    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:.5rem;padding:1rem;text-align:center">
                                        <div style="font-size:.75rem;color:#fca5a5;text-transform:uppercase;letter-spacing:.05em">Egresos (incl. esta factura)</div>
                                        <div style="font-size:1.5rem;font-weight:700;color:#ef4444">$' . number_format($egresos, 2) . '</div>
                                    </div>
                                    <div style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.3);border-radius:.5rem;padding:1rem;text-align:center">
                                        <div style="font-size:.75rem;color:#a5b4fc;text-transform:uppercase;letter-spacing:.05em">Utilidad estimada</div>
                                        <div style="font-size:1.5rem;font-weight:700;color:' . $color . '">$' . number_format($utilidad, 2) . '</div>
                                    </div>
                                </div>
                            ');
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shipment.numero_embarque')
                    ->label('Embarque')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->descripcion),

                TextColumn::make('numero_factura_proveedor')
                    ->label('N.° Factura')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('iva_pct')
                    ->label('IVA')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'por_pagar' => 'warning',
                        'pagada'    => 'success',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'por_pagar' => 'Por pagar',
                        'pagada'    => 'Pagada',
                        default     => $state,
                    }),

                TextColumn::make('fecha_factura')
                    ->label('Fecha factura')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fecha_pago')
                    ->label('Fecha pago')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(['por_pagar' => 'Por pagar', 'pagada' => 'Pagada']),

                SelectFilter::make('shipment_id')
                    ->label('Embarque')
                    ->options(fn () => LogisticsShipment::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->with('consignatario')
                        ->orderByDesc('created_at')
                        ->get()
                        ->mapWithKeys(fn ($s) => [
                            $s->id => implode(' — ', array_filter([
                                $s->numero_embarque,
                                $s->consignatario?->nombre,
                                $s->numero_guia_aerea,
                            ])),
                        ])),

                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->options(fn () => Supplier::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->pluck('nombre', 'id')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('marcar_pagada')
                        ->label('Marcar como pagada')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->estado === 'por_pagar')
                        ->form([
                            DatePicker::make('fecha_pago')
                                ->label('Fecha de pago')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'estado'     => 'pagada',
                                'fecha_pago' => $data['fecha_pago'],
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Factura marcada como pagada')
                                ->success()->send();
                        }),

                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLogisticsShipmentBills::route('/'),
            'create' => Pages\CreateLogisticsShipmentBill::route('/create'),
            'edit'   => Pages\EditLogisticsShipmentBill::route('/{record}/edit'),
        ];
    }
}
