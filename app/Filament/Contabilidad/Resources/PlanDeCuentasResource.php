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
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $modelLabel = 'cuenta';
    protected static ?string $pluralModelLabel = 'Plan de cuentas';
    protected static ?int    $navigationSort = 1;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    /** Las líneas que la Superintendencia espera ver en cada estado. */
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
                    // Una sola forma de decir a qué línea suma: el código del
                    // catálogo. Antes había tres campos para lo mismo y dos de
                    // ellos no casaban con nada.
                    Forms\Components\Select::make('codigo_supercias')
                        ->label('Línea del estado')
                        ->options(fn () => \App\Models\CatalogoSupercias::query()
                            ->whereNotNull('nombre')
                            ->orderBy('estado')->orderBy('orden')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->codigo => $c->codigo . ' · ' . $c->nombre])
                            ->all())
                        ->searchable()
                        ->columnSpanFull()
                        ->helperText('El mismo código que viaja en el archivo del portal. El sistema la '
                            . 'pone sola al crear la cuenta; cambiarla aquí la deja confirmada.')
                        ->afterStateUpdated(fn ($state, $record) => $record?->forceFill([
                            'codigo_supercias_confianza' => 100,
                            'codigo_supercias_revisado'  => true,
                        ])->saveQuietly()),
                ]),
            Forms\Components\Section::make('Uso')->columns(2)->schema([
                Forms\Components\Toggle::make('accepts_movements')->label('Acepta movimientos')->default(true),
                Forms\Components\Toggle::make('is_active')->label('Activa')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
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
                // Una sola columna para la línea: antes había dos del mismo
                // campo, una con el código y otra con el nombre.
                Tables\Columns\TextColumn::make('codigo_supercias')->label('Línea del estado')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state) => $state
                        ? \App\Models\CatalogoSupercias::where('codigo', $state)->value('nombre')
                        : null)
                    ->description(fn ($record) => match (true) {
                        (bool) $record->codigo_supercias => $record->codigo_supercias
                            . ($record->codigo_supercias_revisado ? ' · confirmada' : ' · propuesta del sistema'),
                        ! $record->accepts_movements => 'agrupa a sus hijas, no suma por sí sola',
                        default => 'sin línea: avísame, no debería pasar',
                    })
                    // Las cuentas de agrupación no llevan código porque el
                    // catálogo consolida por prefijo: marcarlas en rojo sería
                    // una alarma falsa.
                    ->placeholder(fn ($record) => $record->accepts_movements ? 'sin asignar' : '—')
                    ->color(fn ($record) => match (true) {
                        ! $record->accepts_movements => 'gray',
                        (bool) $record->codigo_supercias_revisado => 'success',
                        (bool) $record->codigo_supercias => 'warning',
                        default => 'danger',
                    })
                    ->wrap(),
                Tables\Columns\IconColumn::make('accepts_movements')->label('Movimientos')->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('sin_codigo')
                    ->label('Sin código de Supercías')
                    ->query(fn (Builder $q) => $q->whereNull('codigo_supercias')->where('accepts_movements', true)),
                Tables\Filters\Filter::make('sin_mapear')
                    ->label('Sin línea del estado')
                    ->query(fn (Builder $q) => $q->whereNull('codigo_supercias')->where('accepts_movements', true)),
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
                        Forms\Components\Select::make('codigo_supercias')->label('Línea del estado')
                            ->options(fn () => \App\Models\CatalogoSupercias::query()
                                ->whereNotNull('nombre')->orderBy('estado')->orderBy('orden')->get()
                                ->mapWithKeys(fn ($c) => [$c->codigo => $c->codigo . ' · ' . $c->nombre])
                                ->all())
                            ->searchable()->required(),
                    ])
                    ->action(function (array $data, $records) {
                        // Asignada a mano es asignada por una persona: queda confirmada.
                        foreach ($records as $cuenta) {
                            $cuenta->forceFill([
                                'codigo_supercias'           => $data['codigo_supercias'],
                                'codigo_supercias_confianza' => 100,
                                'codigo_supercias_revisado'  => true,
                            ])->save();
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
