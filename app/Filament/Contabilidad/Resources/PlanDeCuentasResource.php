<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\PlanDeCuentasResource\Pages;
use App\Models\AccountPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Plan de cuentas con la pieza que faltaba: a qué línea del estado suma cada
 * cuenta. Sin ese dato el balance se arma a mano en cada presentación.
 */
class PlanDeCuentasResource extends Resource
{
    protected static ?string $model = AccountPlan::class;
    protected static ?string $navigationIcon  = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Plan de cuentas';
    protected static ?string $modelLabel = 'cuenta';
    protected static ?string $pluralModelLabel = 'Plan de cuentas';
    protected static ?int    $navigationSort = 2;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    /** Las líneas que la Superintendencia espera ver en cada estado. */
    public const LINEAS = [
        'Estado de situación financiera' => [
            'activo_corriente'      => 'Activo corriente',
            'activo_no_corriente'   => 'Activo no corriente',
            'pasivo_corriente'      => 'Pasivo corriente',
            'pasivo_no_corriente'   => 'Pasivo no corriente',
            'patrimonio'            => 'Patrimonio',
        ],
        'Estado de resultados' => [
            'ingresos_ordinarios'   => 'Ingresos de actividades ordinarias',
            'otros_ingresos'        => 'Otros ingresos',
            'costo_ventas'          => 'Costo de ventas',
            'gastos_operativos'     => 'Gastos operativos',
            'gastos_financieros'    => 'Gastos financieros',
            'impuestos'             => 'Impuesto a la renta',
        ],
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('La cuenta')->columns(2)->schema([
                Forms\Components\TextInput::make('code')->label('Código')->required()->maxLength(20)
                    ->helperText('La raíz manda: 1 Activo · 2 Pasivo · 3 Patrimonio · 4 Ingresos · 5 Costos · 6 Gastos'),
                Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(160),
                Forms\Components\Select::make('type')->label('Tipo')->required()->options([
                    'activo' => 'Activo', 'pasivo' => 'Pasivo', 'patrimonio' => 'Patrimonio',
                    'ingreso' => 'Ingreso', 'costo' => 'Costo', 'gasto' => 'Gasto',
                ]),
                Forms\Components\Select::make('nature')->label('Naturaleza')->required()
                    ->options(['deudora' => 'Deudora', 'acreedora' => 'Acreedora']),
                Forms\Components\TextInput::make('parent_code')->label('Cuenta padre')->maxLength(20),
                Forms\Components\TextInput::make('level')->label('Nivel')->numeric()->default(4),
            ]),
            Forms\Components\Section::make('Presentación ante la Superintendencia')
                ->description('Sin esto la cuenta sale en cero en el archivo que se sube al portal.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('estado_financiero')->label('Estado financiero')
                        ->options(array_combine(array_keys(self::LINEAS), array_keys(self::LINEAS)))
                        ->live(),
                    Forms\Components\Select::make('linea_estado')->label('Línea del estado')
                        ->options(fn (Forms\Get $get) => self::LINEAS[$get('estado_financiero')] ?? [])
                        ->helperText('Agrupación legible para los informes internos.'),
                    Forms\Components\Select::make('codigo_supercias')
                        ->label('Código del catálogo de la Superintendencia')
                        ->options(fn () => \App\Models\CatalogoSupercias::query()
                            ->whereNotNull('nombre')
                            ->orderBy('estado')->orderBy('orden')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->codigo => $c->codigo . ' · ' . $c->nombre])
                            ->all())
                        ->searchable()
                        ->columnSpanFull()
                        ->helperText('Es el código que viaja en el archivo .txt: 10101, 1010102…'),
                ]),
            Forms\Components\Section::make('Uso')->columns(2)->schema([
                Forms\Components\Toggle::make('accepts_movements')->label('Acepta movimientos')->default(true),
                Forms\Components\Toggle::make('is_active')->label('Activa')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $lineas = collect(self::LINEAS)->flatMap(fn ($l) => $l)->all();

        return $table
            ->defaultSort('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable()->sortable()
                    ->weight(fn ($record) => strlen($record->code) <= 3 ? 'bold' : null),
                Tables\Columns\TextColumn::make('name')->label('Cuenta')->searchable()
                    ->description(fn ($record) => $record->parent_code ? 'de ' . $record->parent_code : null),
                Tables\Columns\TextColumn::make('nature')->label('Naturaleza')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'deudora' ? 'Deudora' : 'Acreedora')
                    ->color(fn (?string $state) => $state === 'deudora' ? 'info' : 'warning'),
                Tables\Columns\TextColumn::make('codigo_supercias')->label('Código SCVS')
                    ->searchable()
                    ->badge()
                    ->placeholder('sin asignar')
                    ->color(fn (?string $state) => $state ? 'success' : 'danger')
                    ->description(fn ($record) => \App\Models\CatalogoSupercias::where('codigo', $record->codigo_supercias)->value('nombre')),
                Tables\Columns\TextColumn::make('linea_estado')->label('Línea del estado')
                    ->formatStateUsing(fn (?string $state) => $lineas[$state] ?? null)
                    ->description(fn ($record) => $record->estado_financiero)
                    ->placeholder('sin asignar')
                    ->badge()
                    ->color(fn (?string $state) => $state ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('accepts_movements')->label('Movimientos')->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('sin_codigo')
                    ->label('Sin código de Supercías')
                    ->query(fn (Builder $q) => $q->whereNull('codigo_supercias')->where('accepts_movements', true)),
                Tables\Filters\Filter::make('sin_mapear')
                    ->label('Sin línea del estado')
                    ->query(fn (Builder $q) => $q->whereNull('linea_estado')->where('accepts_movements', true)),
                Tables\Filters\SelectFilter::make('type')->label('Tipo')->options([
                    'activo' => 'Activo', 'pasivo' => 'Pasivo', 'patrimonio' => 'Patrimonio',
                    'ingreso' => 'Ingreso', 'costo' => 'Costo', 'gasto' => 'Gasto',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()->slideOver()])
            ->bulkActions([
                Tables\Actions\BulkAction::make('asignar_codigo_scvs')
                    ->label('Asignar código de Supercías')
                    ->icon('heroicon-o-building-library')
                    ->form([
                        Forms\Components\Select::make('codigo_supercias')
                            ->label('Código del catálogo')
                            ->options(fn () => \App\Models\CatalogoSupercias::query()
                                ->whereNotNull('nombre')->orderBy('estado')->orderBy('orden')->get()
                                ->mapWithKeys(fn ($c) => [$c->codigo => $c->codigo . ' · ' . $c->nombre])->all())
                            ->searchable()->required(),
                    ])
                    ->action(function (array $data, $records) {
                        foreach ($records as $cuenta) {
                            $cuenta->update(['codigo_supercias' => $data['codigo_supercias']]);
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('asignar_linea')
                    ->label('Asignar línea del estado')
                    ->icon('heroicon-o-link')
                    ->form([
                        Forms\Components\Select::make('estado_financiero')->label('Estado financiero')
                            ->options(array_combine(array_keys(self::LINEAS), array_keys(self::LINEAS)))
                            ->required()->live(),
                        Forms\Components\Select::make('linea_estado')->label('Línea')
                            ->options(fn (Forms\Get $get) => self::LINEAS[$get('estado_financiero')] ?? [])
                            ->required(),
                    ])
                    ->action(function (array $data, $records) {
                        foreach ($records as $cuenta) {
                            $cuenta->update([
                                'estado_financiero' => $data['estado_financiero'],
                                'linea_estado'      => $data['linea_estado'],
                            ]);
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPlanDeCuentas::route('/')];
    }
}
