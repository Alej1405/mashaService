<?php

namespace App\Filament\App\Resources\DebtResource\RelationManagers;

use App\Models\BankAccount;
use App\Models\CashRegister;
use App\Models\DebtAmortizationLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DebtPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Historial de Pagos';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function form(Form $form): Form
    {
        $debt = $this->getOwnerRecord();

        return $form->schema([
            Forms\Components\Section::make('Información del Pago')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('debt_amortization_line_id')
                        ->label('Cuota a pagar')
                        ->options(function () use ($debt) {
                            return $debt->amortizationLines()
                                ->where('estado', '!=', 'pagada')
                                ->get()
                                ->mapWithKeys(fn ($l) => [
                                    $l->id => "Cuota #{$l->numero_cuota} - Vcto: " . \Carbon\Carbon::parse($l->fecha_vencimiento)->format('d/m/Y') . " - $" . number_format($l->total_cuota, 2),
                                ]);
                        })
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) use ($debt) {
                            if ($state) {
                                $line = DebtAmortizationLine::find($state);
                                if ($line) {
                                    $set('numero_cuota', $line->numero_cuota);
                                    $set('monto_capital', $line->monto_capital);
                                    $set('monto_interes', $line->monto_interes);
                                    $set('monto_mora', 0);
                                }
                            }
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('numero_cuota')
                        ->label('N° de cuota')
                        ->numeric()
                        ->integer()
                        ->nullable(),

                    Forms\Components\DatePicker::make('fecha_pago')
                        ->label('Fecha de pago')
                        ->required()
                        ->default(now()),

                    Forms\Components\TextInput::make('monto_capital')
                        ->label('Capital abonado')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->minValue(0)
                        ->live()
                        ->default(0),

                    Forms\Components\TextInput::make('monto_interes')
                        ->label('Intereses pagados')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->minValue(0)
                        ->live()
                        ->default(0),

                    Forms\Components\TextInput::make('monto_mora')
                        ->label('Mora / Recargo')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->live()
                        ->default(0),

                    Forms\Components\Placeholder::make('total_preview')
                        ->label('Total a pagar')
                        ->content(function (Get $get) {
                            $total = ((float) $get('monto_capital'))
                                + ((float) $get('monto_interes'))
                                + ((float) $get('monto_mora'));
                            return '$ ' . number_format($total, 2);
                        })
                        ->columnSpan(1),
                ]),

            Forms\Components\Section::make('Método de Pago')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('metodo_pago')
                        ->label('Método de pago')
                        ->options([
                            'efectivo'     => 'Efectivo',
                            'transferencia' => 'Transferencia Bancaria',
                            'tarjeta'      => 'Tarjeta',
                        ])
                        ->required()
                        ->default('transferencia')
                        ->live(),

                    Forms\Components\Select::make('bank_account_id')
                        ->label('Cuenta bancaria (salida)')
                        ->options(fn () => BankAccount::query()->get()->mapWithKeys(fn ($b) => [$b->id => $b->nombre_completo]))
                        ->searchable()
                        ->nullable()
                        ->visible(fn (Get $get) => in_array($get('metodo_pago'), ['transferencia', 'tarjeta'])),

                    Forms\Components\Select::make('cash_register_id')
                        ->label('Caja (salida de efectivo)')
                        ->options(fn () => CashRegister::query()->pluck('nombre', 'id'))
                        ->searchable()
                        ->nullable()
                        ->visible(fn (Get $get) => $get('metodo_pago') === 'efectivo'),
                ]),

            Forms\Components\Section::make('Comprobante')
                ->schema([
                    Forms\Components\FileUpload::make('comprobante')
                        ->label('Foto del comprobante (opcional)')
                        ->image()
                        ->imageEditor()
                        ->directory('debt-comprobantes')
                        ->visibility('private')
                        ->nullable()
                        ->maxSize(5120)
                        ->helperText('JPG, PNG, máx 5 MB'),

                    Forms\Components\Textarea::make('notas')
                        ->label('Notas adicionales')
                        ->rows(2)
                        ->nullable(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero')
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N° Pago')
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('numero_cuota')
                    ->label('Cuota')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '—')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_capital')
                    ->label('Capital')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('monto_interes')
                    ->label('Intereses')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('monto_mora')
                    ->label('Mora')
                    ->money('USD')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'efectivo'      => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'tarjeta'       => 'Tarjeta',
                        default         => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'efectivo'      => 'success',
                        'transferencia' => 'info',
                        'tarjeta'       => 'warning',
                        default         => 'gray',
                    }),

                Tables\Columns\IconColumn::make('comprobante')
                    ->label('Comprobante')
                    ->boolean()
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('heroicon-o-x-mark'),

                Tables\Columns\TextColumn::make('journalEntry.numero')
                    ->label('Asiento')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Registrar Pago')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalle del Pago'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('fecha_pago', 'desc')
            ->paginated(false);
    }
}
