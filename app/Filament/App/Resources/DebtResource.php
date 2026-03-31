<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DebtResource\Pages;
use App\Filament\App\Resources\DebtResource\RelationManagers;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\Debt;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DebtResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Deudas y Préstamos';

    protected static ?string $modelLabel = 'Deuda';

    protected static ?string $pluralModelLabel = 'Deudas';

    protected static ?string $navigationGroup = 'Financiamiento';

    protected static ?int $navigationSort = 1;

    // Resuelve el error de tenant relationship
    protected static ?string $tenantRelationshipName = 'debts';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([

                // ─── PASO 1: Información General ───────────────────────────
                Forms\Components\Wizard\Step::make('Información General')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo de deuda')
                            ->options([
                                'prestamo_bancario'    => 'Préstamo Bancario',
                                'tarjeta_credito'      => 'Tarjeta de Crédito',
                                'prestamo_personal'    => 'Préstamo Personal',
                                'prestamo_empresarial' => 'Préstamo Empresarial',
                                'otro'                 => 'Otro',
                            ])
                            ->required()
                            ->live()
                            ->default('prestamo_bancario')
                            ->columnSpan(1),

                        Forms\Components\Select::make('estado')
                            ->label('Estado inicial')
                            ->options([
                                'borrador' => 'Borrador (sin asiento contable)',
                                'activa'   => 'Activa (genera asiento al guardar)',
                            ])
                            ->default('borrador')
                            ->columnSpan(1),

                        // Acreedor: Select de banco registrado o "Otro"
                        Forms\Components\Select::make('bank_id')
                            ->label('Institución Financiera / Acreedor')
                            ->options(function () {
                                $bancos = Bank::activos()->orderBy('nombre')
                                    ->get()
                                    ->groupBy('tipo')
                                    ->map(fn ($grupo) => $grupo->pluck('nombre', 'id'));

                                $opciones = [];
                                $tipos = [
                                    'banco_privado' => 'Bancos Privados',
                                    'banco_publico' => 'Bancos Públicos',
                                    'cooperativa'   => 'Cooperativas',
                                    'mutualista'    => 'Mutualistas',
                                    'financiera'    => 'Financieras',
                                ];
                                foreach ($tipos as $tipo => $label) {
                                    if (isset($bancos[$tipo])) {
                                        $opciones[$label] = $bancos[$tipo]->toArray();
                                    }
                                }

                                return $opciones;
                            })
                            ->searchable()
                            ->placeholder('Seleccionar institución...')
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state) {
                                    $banco = Bank::find($state);
                                    if ($banco) {
                                        $set('acreedor', $banco->nombre);
                                    }
                                } else {
                                    $set('acreedor', null);
                                }
                            })
                            ->helperText('Si no está en la lista, deja vacío e ingresa el nombre manualmente abajo.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('acreedor')
                            ->label(fn (Get $get) => $get('bank_id') ? 'Nombre del acreedor (auto-completado)' : 'Nombre del acreedor / persona')
                            ->placeholder('Ej: Juan Pérez, BIESS, otra institución...')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('¿Para qué se solicitó este préstamo?')
                            ->placeholder('Compra de maquinaria, capital de trabajo, remodelación...')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // ─── PASO 2: Condiciones Financieras ───────────────────────
                Forms\Components\Wizard\Step::make('Condiciones del Préstamo')
                    ->icon('heroicon-o-calculator')
                    ->schema([

                        // ── Selector de modo de ingreso ──────────────────────
                        Forms\Components\ToggleButtons::make('modo_ingreso')
                            ->label('¿Cómo deseas registrar las condiciones?')
                            ->options([
                                'calcular'   => 'Calcular la cuota',
                                'cuota_fija' => 'Ya tengo la cuota mensual',
                            ])
                            ->icons([
                                'calcular'   => 'heroicon-o-calculator',
                                'cuota_fija' => 'heroicon-o-check-badge',
                            ])
                            ->colors([
                                'calcular'   => 'info',
                                'cuota_fija' => 'success',
                            ])
                            ->default(fn ($record) => ($record && $record->cuota_mensual) ? 'cuota_fija' : 'calcular')
                            ->live()
                            ->dehydrated(false)
                            ->grouped()
                            ->columnSpanFull()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state === 'cuota_fija') {
                                    $set('sistema_amortizacion', 'frances');
                                    static::recalcularCapitalDesdeCuota($get, $set);
                                }
                            }),

                        // ── Monto del préstamo ────────────────────────────────
                        // Modo calcular: el usuario lo ingresa
                        // Modo cuota_fija: se auto-calcula y queda de solo lectura
                        Forms\Components\TextInput::make('monto_original')
                            ->label(fn (Get $get) => $get('modo_ingreso') === 'cuota_fija'
                                ? 'Capital del préstamo (calculado automáticamente)'
                                : 'Monto del préstamo')
                            ->numeric()
                            ->required(fn (Get $get) => $get('modo_ingreso') !== 'cuota_fija')
                            ->prefix('$')
                            ->minValue(0.01)
                            ->readOnly(fn (Get $get) => $get('modo_ingreso') === 'cuota_fija')
                            ->helperText(fn (Get $get) => $get('modo_ingreso') === 'cuota_fija'
                                ? 'Se calcula automáticamente desde la cuota, el plazo y la tasa ingresados.'
                                : null)
                            ->columnSpan(1),

                        // ── Cuota mensual conocida (solo modo cuota_fija) ─────
                        Forms\Components\TextInput::make('cuota_mensual')
                            ->label('Cuota mensual')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0.01)
                            ->required(fn (Get $get) => $get('modo_ingreso') === 'cuota_fija')
                            ->visible(fn (Get $get) => $get('modo_ingreso') === 'cuota_fija')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcularCapitalDesdeCuota($get, $set))
                            ->columnSpan(1),

                        // ── Tasa de interés (compartido) ──────────────────────
                        Forms\Components\TextInput::make('tasa_interes')
                            ->label('Tasa de interés (TNA %)')
                            ->numeric()
                            ->required()
                            ->suffix('% anual')
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Tasa Nominal Anual. Ej: 15.60 para 15.60% anual.')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if ($get('modo_ingreso') === 'cuota_fija') {
                                    static::recalcularCapitalDesdeCuota($get, $set);
                                }
                            })
                            ->columnSpan(1),

                        // ── Seguro de desgravamen (compartido) ────────────────
                        Forms\Components\TextInput::make('seguro_desgravamen_anual')
                            ->label('Seguro de desgravamen (tasa anual nominal)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.0001)
                            ->helperText('Tasa anual nominal. Se aplica mensualmente sobre el saldo. Ej: 0.35. Dejar en 0 si no aplica.')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if ($get('modo_ingreso') === 'cuota_fija') {
                                    static::recalcularCapitalDesdeCuota($get, $set);
                                }
                            })
                            ->columnSpan(1),

                        // ── Sistema de amortización (solo modo calcular) ──────
                        Forms\Components\Select::make('sistema_amortizacion')
                            ->label('Sistema de Amortización')
                            ->options([
                                'frances'   => 'Francés (cuota fija, interés sobre saldo decreciente)',
                                'aleman'    => 'Alemán (capital fijo, cuota decreciente)',
                                'americano' => 'Americano (solo intereses, capital al final)',
                            ])
                            ->required()
                            ->default('frances')
                            ->visible(fn (Get $get) => $get('modo_ingreso') !== 'cuota_fija')
                            ->columnSpan(1),

                        // ── Desglose estimado (solo modo cuota_fija) ──────────
                        Forms\Components\Placeholder::make('desglose_preview')
                            ->label('Desglose estimado del préstamo')
                            ->content(fn (Get $get) => static::previewDesgloseCuotaFija($get))
                            ->visible(fn (Get $get) => $get('modo_ingreso') === 'cuota_fija')
                            ->columnSpanFull(),

                        // ── Fecha de inicio (compartido) ──────────────────────
                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $plazo = (int) $get('plazo_meses');
                                if ($state && $plazo > 0) {
                                    $set('fecha_vencimiento', Carbon::parse($state)->addMonths($plazo)->toDateString());
                                }
                            })
                            ->columnSpan(1),

                        // ── Plazo (compartido) ────────────────────────────────
                        Forms\Components\TextInput::make('plazo_meses')
                            ->label('Plazo total (meses)')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $plazo = (int) $state;

                                $inicio = $get('fecha_inicio');
                                if ($inicio && $plazo > 0) {
                                    $set('fecha_vencimiento', Carbon::parse($inicio)->addMonths($plazo)->toDateString());
                                }

                                if ($plazo > 0) {
                                    $set('numero_cuotas', $plazo);
                                }

                                $set('clasificacion', $plazo <= 12 ? 'corriente' : 'no_corriente');

                                if ($get('modo_ingreso') === 'cuota_fija') {
                                    static::recalcularCapitalDesdeCuota($get, $set);
                                }
                            })
                            ->columnSpan(1),

                        // ── Fecha de vencimiento (compartido) ─────────────────
                        Forms\Components\DatePicker::make('fecha_vencimiento')
                            ->label('Fecha de vencimiento')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $inicio = $get('fecha_inicio');
                                if ($inicio && $state) {
                                    $meses = (int) Carbon::parse($inicio)->diffInMonths(Carbon::parse($state));
                                    if ($meses > 0) {
                                        $set('plazo_meses', $meses);
                                        $set('numero_cuotas', $meses);
                                        $set('clasificacion', $meses <= 12 ? 'corriente' : 'no_corriente');
                                        if ($get('modo_ingreso') === 'cuota_fija') {
                                            static::recalcularCapitalDesdeCuota($get, $set);
                                        }
                                    }
                                }
                            })
                            ->columnSpan(1),

                        // ── Número de cuotas (compartido) ─────────────────────
                        Forms\Components\TextInput::make('numero_cuotas')
                            ->label('Número de cuotas')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->helperText('Auto-calculado del plazo. Puedes ajustarlo si las cuotas no son mensuales.')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if ($get('modo_ingreso') === 'cuota_fija') {
                                    static::recalcularCapitalDesdeCuota($get, $set);
                                }
                            })
                            ->columnSpan(1),

                        // ── Clasificación contable (compartido) ───────────────
                        Forms\Components\Placeholder::make('clasificacion_preview')
                            ->label('Clasificación contable (automática)')
                            ->content(function (Get $get) {
                                $plazo = (int) $get('plazo_meses');
                                if (!$plazo) return '— Ingresa el plazo para calcular —';
                                return $plazo <= 12
                                    ? '✅ CORRIENTE (≤ 12 meses) — Pasivo Corriente'
                                    : '📋 NO CORRIENTE (> 12 meses) — Pasivo No Corriente';
                            })
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('clasificacion')
                            ->default('corriente'),
                    ])
                    ->columns(2),

                // ─── PASO 3: Cuenta del Acreedor y Pago ────────────────────
                Forms\Components\Wizard\Step::make('Datos de Pago')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Nuestra cuenta donde ingresó el dinero')
                            ->options(fn () => BankAccount::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->nombre_completo]))
                            ->searchable()
                            ->nullable()
                            ->helperText('Cuenta propia donde se depositó el préstamo')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('credit_card_id')
                            ->label('Tarjeta de crédito (si aplica)')
                            ->options(fn () => CreditCard::query()->get()->mapWithKeys(fn ($c) => [$c->id => $c->nombre ?? "TC-{$c->id}"]))
                            ->searchable()
                            ->nullable()
                            ->visible(fn (Get $get) => $get('tipo') === 'tarjeta_credito')
                            ->columnSpanFull(),

                        // Banco donde enviamos los pagos (campo virtual, no se guarda en BD)
                        Forms\Components\Select::make('banco_acreedor_select')
                            ->label('Banco del acreedor (para enviar pagos)')
                            ->options(function () {
                                return Bank::activos()->orderBy('nombre')
                                    ->pluck('nombre', 'nombre')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable()
                            ->placeholder('Seleccionar o escribir...')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(fn (Set $set, $state) => $set('banco_acreedor', $state))
                            ->helperText('Selecciona de la lista o escribe abajo si no está.')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('banco_acreedor')
                            ->label('Banco del acreedor (personalizado si no está en la lista)')
                            ->placeholder('Dejar vacío si seleccionaste arriba')
                            ->maxLength(100)
                            ->nullable()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('cuenta_pago_acreedor')
                            ->label('N° de cuenta del acreedor (para pagos)')
                            ->placeholder('1234567890')
                            ->maxLength(50)
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notas')
                            ->label('Notas adicionales')
                            ->rows(2)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

            ])
            ->skippable()
            ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('acreedor')
                    ->label('Acreedor')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Debt $record) => $record->bank?->nombre ? "({$record->bank->tipo})" : null),

                Tables\Columns\BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'prestamo_bancario'    => 'Bancario',
                        'tarjeta_credito'      => 'Tarjeta',
                        'prestamo_personal'    => 'Personal',
                        'prestamo_empresarial' => 'Empresarial',
                        default                => 'Otro',
                    })
                    ->color(fn ($state) => match ($state) {
                        'prestamo_bancario'    => 'info',
                        'tarjeta_credito'      => 'warning',
                        'prestamo_personal'    => 'primary',
                        'prestamo_empresarial' => 'success',
                        default                => 'gray',
                    }),

                Tables\Columns\TextColumn::make('monto_original')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tasa_interes')
                    ->label('Tasa TNA')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '% anual')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('plazo_meses')
                    ->label('Plazo')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} meses" : '—')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo Pendiente')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (Debt $record) => match ($record->estado) {
                        'pagada'  => 'success',
                        'vencida' => 'danger',
                        'parcial' => 'warning',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Debt $record) => $record->fecha_vencimiento < now()->toDateString() && $record->estado !== 'pagada' ? 'danger' : null),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->color(fn ($state) => match ($state) {
                        'borrador'     => 'gray',
                        'activa'       => 'info',
                        'parcial'      => 'warning',
                        'pagada'       => 'success',
                        'vencida'      => 'danger',
                        'refinanciada' => 'primary',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'prestamo_bancario'    => 'Préstamo Bancario',
                        'tarjeta_credito'      => 'Tarjeta de Crédito',
                        'prestamo_personal'    => 'Préstamo Personal',
                        'prestamo_empresarial' => 'Préstamo Empresarial',
                        'otro'                 => 'Otro',
                    ]),

                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'borrador'     => 'Borrador',
                        'activa'       => 'Activa',
                        'parcial'      => 'Parcial',
                        'pagada'       => 'Pagada',
                        'vencida'      => 'Vencida',
                        'refinanciada' => 'Refinanciada',
                    ]),

                Tables\Filters\SelectFilter::make('clasificacion')
                    ->label('Clasificación')
                    ->options([
                        'corriente'    => 'Corriente (≤ 12 meses)',
                        'no_corriente' => 'No Corriente (> 12 meses)',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activar Deuda')
                    ->modalDescription('Al activar se generará el asiento contable y la tabla de amortización.')
                    ->visible(fn (Debt $record) => $record->estado === 'borrador')
                    ->action(function (Debt $record) {
                        try {
                            $record->update(['estado' => 'activa']);
                            \Filament\Notifications\Notification::make()
                                ->title('Deuda activada')
                                ->body("Asiento {$record->fresh()->journalEntry?->numero} generado correctamente.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al activar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AmortizationLinesRelationManager::class,
            RelationManagers\DebtPaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDebts::route('/'),
            'create' => Pages\CreateDebt::route('/create'),
            'view'   => Pages\ViewDebt::route('/{record}'),
            'edit'   => Pages\EditDebt::route('/{record}/edit'),
        ];
    }

    /**
     * Back-calcula el monto del préstamo desde cuota, tasa y número de cuotas.
     * Fórmula PV del sistema francés: PV = PMT × (1 − (1+r)^-n) / r
     */
    private static function recalcularCapitalDesdeCuota(Get $get, Set $set): void
    {
        $cuota  = (float) $get('cuota_mensual');
        $tasa   = (float) $get('tasa_interes');
        $seguro = (float) $get('seguro_desgravamen_anual');
        $cuotas = (int) ($get('numero_cuotas') ?: $get('plazo_meses'));

        if ($cuota > 0 && $cuotas > 0) {
            $r = ($tasa + $seguro) / 100 / 12;
            $monto = $r > 0
                ? round($cuota * (1 - pow(1 + $r, -$cuotas)) / $r, 2)
                : round($cuota * $cuotas, 2);
            $set('monto_original', $monto);
        }
    }

    /**
     * Genera el HTML del desglose estimado para el modo cuota_fija.
     */
    private static function previewDesgloseCuotaFija(Get $get): string|\Illuminate\Support\HtmlString
    {
        $cuota  = (float) $get('cuota_mensual');
        $tasa   = (float) $get('tasa_interes');
        $seguro = (float) $get('seguro_desgravamen_anual');
        $cuotas = (int) ($get('numero_cuotas') ?: $get('plazo_meses'));

        if (!$cuota || !$cuotas) {
            return '— Ingresa la cuota mensual, el plazo y la tasa para ver el desglose —';
        }

        $r = ($tasa + $seguro) / 100 / 12;
        $monto        = $r > 0 ? $cuota * (1 - pow(1 + $r, -$cuotas)) / $r : $cuota * $cuotas;
        $totalPagar   = round($cuota * $cuotas, 2);
        $totalInteres = round($totalPagar - $monto, 2);
        $monto        = round($monto, 2);

        return new \Illuminate\Support\HtmlString(
            '<div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">' .
            '<div><p class="text-gray-500 dark:text-gray-400">Capital del préstamo</p><p class="font-bold text-blue-700 dark:text-blue-400 text-base">$ ' . number_format($monto, 2) . '</p></div>' .
            '<div><p class="text-gray-500 dark:text-gray-400">Cuota mensual</p><p class="font-bold text-green-700 dark:text-green-400 text-base">$ ' . number_format($cuota, 2) . '</p></div>' .
            '<div><p class="text-gray-500 dark:text-gray-400">Total a pagar (' . $cuotas . ' cuotas)</p><p class="font-bold text-gray-800 dark:text-gray-200">$ ' . number_format($totalPagar, 2) . '</p></div>' .
            '<div><p class="text-gray-500 dark:text-gray-400">Total intereses + seguro</p><p class="font-bold text-orange-600 dark:text-orange-400">$ ' . number_format($totalInteres, 2) . '</p></div>' .
            '</div>'
        );
    }
}
