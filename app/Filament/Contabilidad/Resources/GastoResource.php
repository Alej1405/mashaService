<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\GastoResource\Pages;
use App\Models\Gasto;
use App\Models\TipoGasto;
use App\Services\GastoService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Gastos que no pasan por inventario.
 *
 * Alimentación, transporte, servicios básicos, arriendo, honorarios: todo lo
 * que sostiene el estado de resultados y que hasta ahora no tenía dónde
 * registrarse. Cada línea dice si es deducible, porque el 101 lo pide aparte.
 */
class GastoResource extends Resource
{
    protected static ?string $model = Gasto::class;
    protected static ?string $slug = 'gastos';
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Gastos';
    protected static ?string $modelLabel = 'gasto';
    protected static ?string $pluralModelLabel = 'Gastos';
    protected static ?int    $navigationSort = 2;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Comprobante')->columns(3)->schema([
                Forms\Components\Select::make('tipo_documento')->label('Tipo')
                    ->options(['factura' => 'Factura', 'nota_venta' => 'Nota de venta',
                               'liquidacion' => 'Liquidación de compra', 'recibo' => 'Recibo sin sustento'])
                    ->default('factura')->required(),
                Forms\Components\TextInput::make('numero_documento')->label('Número')->maxLength(30)
                    ->placeholder('001-001-000000123'),
                Forms\Components\DatePicker::make('fecha')->label('Fecha')->default(now())->required()
                    ->helperText('Decide los porcentajes de retención que se aplican.'),
                Forms\Components\Select::make('supplier_id')->label('Proveedor')
                    ->relationship('supplier', 'nombre')->searchable()->preload()->columnSpan(2),
                Forms\Components\Select::make('forma_pago')->label('Forma de pago')
                    ->options(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia',
                               'tarjeta' => 'Tarjeta', 'credito' => 'Crédito'])->default('efectivo'),
                Forms\Components\TextInput::make('descripcion')->label('Concepto')->maxLength(200)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Detalle')
                ->description('El IVA es crédito tributario, no gasto. Lo no deducible se registra igual y se concilia después.')
                ->schema([
                    Forms\Components\Repeater::make('lineas')
                        ->relationship()
                        ->label('')
                        ->columns(12)
                        ->defaultItems(1)
                        ->schema([
                            Forms\Components\Select::make('tipo_gasto_id')->label('Tipo de gasto')
                                ->options(fn () => TipoGasto::disponibles(Filament::getTenant()?->id)->orderBy('nombre')->pluck('nombre', 'id'))
                                ->searchable()->required()->live()
                                ->columnSpan(4)
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($t = TipoGasto::find($state)) {
                                        $set('account_plan_id', $t->account_plan_id);
                                        $set('deducible', $t->deducible);
                                    }
                                }),
                            Forms\Components\Select::make('account_plan_id')->label('Cuenta')
                                ->options(fn () => \App\Models\AccountPlan::withoutGlobalScopes()
                                    ->where('empresa_id', Filament::getTenant()?->id)
                                    ->where('accepts_movements', true)
                                    ->whereIn(\Illuminate\Support\Facades\DB::raw('left(code,1)'), ['5', '6'])
                                    ->orderBy('code')->get()
                                    ->mapWithKeys(fn ($c) => [$c->id => $c->code . ' · ' . $c->name])->all())
                                ->searchable()->required()->columnSpan(4),
                            Forms\Components\TextInput::make('base')->label('Base')->numeric()->prefix('$')
                                ->required()->live(onBlur: true)->columnSpan(2)
                                ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) =>
                                    $set('iva', round(((float) $state) * ((float) $get('porcentaje_iva')) / 100, 2))),
                            Forms\Components\Select::make('porcentaje_iva')->label('IVA')
                                ->options(fn () => \App\Models\TarifaIva::query()
                                    ->whereNull('vigente_hasta')->orderByDesc('porcentaje')->get()
                                    ->mapWithKeys(fn ($t) => [(string) (float) $t->porcentaje =>
                                        rtrim(rtrim(number_format($t->porcentaje, 2, ',', '.'), '0'), ',') . ' %'])->all())
                                ->default('15')->live()->columnSpan(1)
                                ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) =>
                                    $set('iva', round(((float) $get('base')) * ((float) $state) / 100, 2))),
                            Forms\Components\TextInput::make('iva')->label('IVA $')->numeric()->prefix('$')
                                ->readOnly()->columnSpan(1),
                            Forms\Components\TextInput::make('descripcion')->label('Detalle')->maxLength(200)->columnSpan(8),
                            Forms\Components\Toggle::make('deducible')->label('Deducible')->default(true)->live()->columnSpan(2),
                            Forms\Components\TextInput::make('motivo_no_deducible')->label('Motivo')
                                ->visible(fn (Forms\Get $get) => ! $get('deducible'))
                                ->maxLength(160)->columnSpan(2),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $money = fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.');

        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('numero_documento')->label('Documento')->searchable()->placeholder('sin número')
                    ->description(fn ($record) => ucfirst(str_replace('_', ' ', $record->tipo_documento))),
                Tables\Columns\TextColumn::make('supplier.nombre')->label('Proveedor')->searchable()->placeholder('—')
                    ->description(fn ($record) => $record->descripcion),
                Tables\Columns\TextColumn::make('subtotal')->label('Base')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('iva')->label('IVA')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('retenido')->label('Retenido')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('total')->label('Total')->alignEnd()->weight('bold')->formatStateUsing($money)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->formatStateUsing($money)),
                Tables\Columns\TextColumn::make('no_deducible')->label('No deducible')->alignEnd()
                    ->formatStateUsing(fn ($state) => (float) $state ? '$ ' . number_format((float) $state, 2, ',', '.') : '—')
                    ->color('danger')->toggleable(),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                    ->color(fn (?string $state) => $state === 'confirmado' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->options(['borrador' => 'Borrador', 'confirmado' => 'Confirmado']),
                Tables\Filters\Filter::make('no_deducibles')->label('Con gasto no deducible')
                    ->query(fn ($query) => $query->where('no_deducible', '>', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('confirmar')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Gasto $record) => $record->estado !== 'confirmado')
                    ->requiresConfirmation()
                    ->modalDescription('Genera el asiento contable y, si la empresa es agente de retención, el comprobante.')
                    ->action(function (Gasto $record) {
                        try {
                            $g = app(GastoService::class)->confirmar($record);
                            Notification::make()->title('Gasto contabilizado')
                                ->body('Total ' . number_format((float) $g->total, 2, ',', '.')
                                    . ($g->retenido > 0 ? ' · retenido ' . number_format((float) $g->retenido, 2, ',', '.') : ''))
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('No se pudo contabilizar')->body($e->getMessage())
                                ->danger()->persistent()->send();
                        }
                    }),
                Tables\Actions\EditAction::make()->visible(fn (Gasto $record) => $record->estado !== 'confirmado'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGastos::route('/'),
            'create' => Pages\CrearGasto::route('/nuevo'),
            'edit'   => Pages\EditarGasto::route('/{record}/editar'),
        ];
    }
}
