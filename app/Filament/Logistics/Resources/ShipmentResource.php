<?php

namespace App\Filament\Logistics\Resources;

use App\Filament\Logistics\Pages\ShipmentKanban;
use App\Filament\Logistics\Resources\ShipmentResource\Pages;
use App\Models\LogisticsBodega;
use App\Models\LogisticsConsignatario;
use App\Models\LogisticsDocument;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentResource extends Resource
{
    protected static ?string $model                  = LogisticsShipment::class;
    protected static ?string $tenantRelationshipName = 'logisticsShipments';
    protected static ?string $navigationIcon         = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Embarques';
    protected static ?string $navigationGroup = 'Importaciones';
    protected static ?string $modelLabel      = 'Embarque';
    protected static ?string $pluralModelLabel = 'Embarques';
    protected static ?int    $navigationSort  = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Datos del embarque ────────────────────────────────────────────
            Section::make('Datos del embarque')->schema([
                TextInput::make('numero_embarque')
                    ->label('Número de embarque')
                    ->placeholder('Se genera automáticamente')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(1),
                Select::make('tipo')
                    ->label('Tipo')
                    ->options(LogisticsShipment::TIPOS)
                    ->required()
                    ->default('individual')
                    ->helperText('Consolidado: varios paquetes juntos. Fraccionado: un pedido dividido.')
                    ->columnSpan(1),
                Select::make('bodega_id')
                    ->label('Bodega de origen')
                    ->options(fn () => LogisticsBodega::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->pluck('nombre', 'id'))
                    ->required()
                    ->searchable()
                    ->columnSpan(2),
            ])->columns(2),

            // ── Consignatario ─────────────────────────────────────────────────
            Section::make('Consignatario')->schema([
                Select::make('consignatario_id')
                    ->label('Consignatario')
                    ->options(fn () => LogisticsConsignatario::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->orderBy('nombre')
                        ->get()
                        ->mapWithKeys(fn ($c) => [
                            $c->id => $c->nombre . ($c->cedula_pasaporte ? ' — ' . $c->cedula_pasaporte : ''),
                        ]))
                    ->required()
                    ->searchable()
                    ->helperText(fn ($state) => $state
                        ? 'Valor declarado acumulado: $' . number_format(
                            LogisticsConsignatario::withoutGlobalScopes()->find($state)?->valor_declarado_acumulado ?? 0, 2
                          )
                        : null)
                    ->createOptionModalHeading('Registrar consignatario')
                    ->createOptionForm([
                        TextInput::make('nombre')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('cedula_pasaporte')
                            ->label('Cédula / RUC')
                            ->required()
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $limpio = preg_replace('/\D/', '', $value);
                                    if (strlen($limpio) === 10 || strlen($limpio) === 13) {
                                        return; // cédula (10) o RUC (13): OK
                                    }
                                    $fail('La cédula debe tener 10 dígitos y el RUC 13 dígitos.');
                                };
                            })
                            ->dehydrateStateUsing(fn ($state) => preg_replace('/\D/', '', $state))
                            ->helperText('Cédula: 10 dígitos · RUC: 13 dígitos'),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $consignatario = LogisticsConsignatario::create([
                            'empresa_id'       => Filament::getTenant()->id,
                            'nombre'           => $data['nombre'],
                            'cedula_pasaporte' => $data['cedula_pasaporte'],
                        ]);
                        return $consignatario->id;
                    })
                    ->columnSpanFull(),
            ]),

            // ── Paquetes ──────────────────────────────────────────────────────
            Section::make('Paquetes incluidos')
                ->description('Solo se muestran paquetes que aún no han sido asignados a otro embarque.')
                ->schema([
                    Select::make('packages')
                        ->label('Paquetes')
                        ->multiple()
                        ->relationship(
                            name: 'packages',
                            titleAttribute: 'descripcion',
                            modifyQueryUsing: function (Builder $query) {
                                // En embarques consolidados con paquetes de distintos clientes,
                                // whereDoesntHave/whereHas pasan por EmpresaScope en LogisticsShipment
                                // y pueden devolver resultados incompletos. Usamos SQL directo sobre
                                // el pivot para evitar cualquier filtrado por scope.
                                $record     = request()->route('record');
                                $shipmentId = $record instanceof LogisticsShipment
                                    ? $record->getKey()
                                    : (is_numeric($record) ? (int) $record : null);

                                return $query->where(function (Builder $q) use ($shipmentId) {
                                    // Paquetes que no están asignados a ningún embarque
                                    $q->whereNotIn('logistics_packages.id', function ($sub) {
                                        $sub->from('logistics_shipment_packages')
                                            ->select('package_id');
                                    });

                                    // O paquetes ya asignados a ESTE embarque (modo edición)
                                    if ($shipmentId) {
                                        $q->orWhereIn('logistics_packages.id', function ($sub) use ($shipmentId) {
                                            $sub->from('logistics_shipment_packages')
                                                ->select('package_id')
                                                ->where('shipment_id', $shipmentId);
                                        });
                                    }
                                })
                                ->where('estado', '!=', 'en_entrega');
                            },
                        )
                        ->getOptionLabelFromRecordUsing(fn (LogisticsPackage $p) =>
                            '[' . ($p->numero_tracking ?? 'PKG-' . $p->id) . '] '
                            . $p->descripcion
                            . ' — $' . number_format($p->valor_declarado, 2)
                            . ($p->peso_kg ? ' · ' . $p->peso_kg . ' kg' : '')
                        )
                        ->searchable()
                        ->preload()
                        ->placeholder('Seleccionar paquetes...')
                        ->columnSpanFull(),
                ]),

            // ── Información logística ─────────────────────────────────────────
            Section::make('Información logística')->schema([
                DatePicker::make('fecha_embarque')->label('Fecha de embarque')->columnSpan(1),
                DatePicker::make('fecha_llegada_ecuador')->label('Fecha de llegada a Ecuador')->columnSpan(1),
                TextInput::make('numero_guia_aerea')->label('Guía aérea / BL')->columnSpan(1),
                TextInput::make('numero_declaracion_aduana')->label('Número de declaración SENAE')->columnSpan(1),
                TextInput::make('valor_total_declarado')
                    ->label('Valor total declarado')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->helperText('Courier SENAE: hasta $400 sin impuestos · $400–$2000 impuesto 42%.')
                    ->columnSpan(1),
                TextInput::make('peso_total_kg')
                    ->label('Peso total (kg)')
                    ->numeric()
                    ->suffix('kg')
                    ->columnSpan(1),
                TextInput::make('impuestos_pagados')
                    ->label('Impuestos pagados ($)')
                    ->numeric()
                    ->prefix('$')
                    ->nullable()
                    ->columnSpan(1),
            ])->columns(2),

            // ── Observaciones y soporte (solo si aplica) ──────────────────────
            Section::make('Observaciones y documentos de soporte')
                ->description('Completa esta sección únicamente si el embarque tiene observaciones o requiere adjuntar documentos.')
                ->collapsed()
                ->schema([
                    Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(3)
                        ->columnSpanFull(),
                    Repeater::make('documentosData')
                        ->label('Documentos adjuntos')
                        ->schema([
                            Select::make('tipo')
                                ->label('Tipo')
                                ->options(LogisticsDocument::TIPOS)
                                ->required()
                                ->columnSpan(1),
                            TextInput::make('nombre')
                                ->label('Nombre del documento')
                                ->required()
                                ->columnSpan(2),
                            FileUpload::make('archivo_path')
                                ->label('Archivo')
                                ->disk('public')
                                ->directory('logistics/documents')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->required()
                                ->columnSpan(2),
                            Textarea::make('notas')
                                ->label('Notas')
                                ->rows(1)
                                ->columnSpan(3),
                        ])
                        ->columns(3)
                        ->addActionLabel('Agregar documento')
                        ->collapsible()
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),

            // ── Historial ─────────────────────────────────────────────────────
            Section::make('Historial del embarque')
                ->icon('heroicon-o-clock')
                ->collapsed()
                ->visibleOn('edit')
                ->schema([
                    ViewField::make('history_timeline')
                        ->view('filament.logistics.components.shipment-history')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_embarque')
                    ->label('Embarque')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'consolidado' => 'warning',
                        'fraccionado' => 'danger',
                        default       => 'info',
                    })
                    ->formatStateUsing(fn ($state) => LogisticsShipment::TIPOS[$state] ?? $state),
                TextColumn::make('consignatario.nombre')
                    ->label('Consignatario')
                    ->searchable()
                    ->limit(25),
                TextColumn::make('bodega.pais')
                    ->label('Origen')
                    ->badge()
                    ->color(fn ($state) => $state === 'EEUU' ? 'info' : 'warning'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => LogisticsShipment::ESTADOS[$state]['label'] ?? $state)
                    ->color(fn ($record) => 'gray'),
                TextColumn::make('valor_total_declarado')
                    ->label('Valor declarado')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('packages_count')
                    ->counts('packages')
                    ->label('Paquetes')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                TextColumn::make('fecha_embarque')
                    ->label('Embarque')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipo')->options(LogisticsShipment::TIPOS),
                SelectFilter::make('estado')
                    ->options(array_map(fn ($v) => $v['label'], LogisticsShipment::ESTADOS))
                    ->label('Estado'),
                SelectFilter::make('bodega_id')
                    ->label('Bodega')
                    ->relationship('bodega', 'nombre'),
            ])
            ->actions([
                Action::make('kanban')
                    ->label('Ver en tablero')
                    ->icon('heroicon-o-view-columns')
                    ->color('gray')
                    ->url(fn () => ShipmentKanban::getUrl(tenant: Filament::getTenant())),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit'   => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}
