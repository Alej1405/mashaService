<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\ActivoFijoResource\Pages;
use App\Models\ActivoFijo;
use App\Services\GastoService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Maquinaria y equipo: no se consume, se deprecia en línea recta. */
class ActivoFijoResource extends Resource
{
    protected static ?string $model = ActivoFijo::class;
    protected static ?string $slug = 'activos-fijos';
    protected static ?string $navigationIcon  = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Activos fijos';
    protected static ?string $modelLabel = 'activo fijo';
    protected static ?string $pluralModelLabel = 'Activos fijos';
    protected static ?int    $navigationSort = 9;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function form(Form $form): Form
    {
        $cuentas = fn (array $raices) => \App\Models\AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()?->id)
            ->where(function ($q) use ($raices) {
                foreach ($raices as $r) $q->orWhere('code', 'like', $r . '%');
            })
            ->orderBy('code')->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->code . ' · ' . $c->name])->all();

        return $form->schema([
            Forms\Components\Section::make('El activo')->columns(3)->schema([
                Forms\Components\TextInput::make('codigo')->label('Código')->maxLength(30)->placeholder('MAQ-001'),
                Forms\Components\TextInput::make('nombre')->label('Nombre')->required()->maxLength(160)->columnSpan(2),
                // No se guarda: solo ayuda a fijar la vida útil. La categoría
                // real del activo es su cuenta contable, que es la que suma en
                // el balance.
                Forms\Components\Select::make('ayuda_categoria')
                    ->label('Tipo de activo')
                    ->helperText('Solo para proponer la vida útil')
                    ->dehydrated(false)
                    ->options(['maquinaria' => 'Maquinaria y equipo', 'vehiculo' => 'Vehículos',
                               'computo' => 'Equipo de cómputo', 'muebles' => 'Muebles y enseres',
                               'inmueble' => 'Inmuebles', 'otro' => 'Otro'])
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // Vidas útiles del reglamento de la LRTI.
                        $set('vida_util_meses', match ($state) {
                            'inmueble' => 240, 'vehiculo' => 60, 'computo' => 36, 'muebles' => 120, default => 120,
                        });
                    }),
                Forms\Components\Select::make('ubicacion_almacen_id')->label('Ubicación')
                    ->relationship('ubicacionAlmacen', 'codigo_ubicacion')->searchable()->preload()->columnSpan(2),
            ]),
            Forms\Components\Section::make('Costo y vida útil')->columns(4)->schema([
                Forms\Components\DatePicker::make('fecha_compra')->label('Fecha de compra')->required()->default(now()),
                Forms\Components\TextInput::make('purchase_price')->label('Costo')->numeric()->prefix('$')
                    ->required()->live(onBlur: true),
                Forms\Components\TextInput::make('valor_residual')->label('Valor residual')->numeric()->prefix('$')->default(0),
                Forms\Components\TextInput::make('vida_util_meses')->label('Vida útil')->numeric()->suffix('meses')
                    ->required()->default(120)->live(onBlur: true)
                    ->helperText(fn (Forms\Get $get) => $get('purchase_price') && $get('vida_util_meses')
                        ? 'Cuota mensual: $ ' . number_format(((float) $get('purchase_price') - (float) $get('valor_residual')) / max((int) $get('vida_util_meses'), 1), 2, ',', '.')
                        : 'Línea recta sobre el costo menos el residual'),
            ]),
            Forms\Components\Section::make('Cuentas')->columns(3)->schema([
                Forms\Components\Select::make('account_plan_id')->label('Activo')->options($cuentas(['1.2']))->searchable(),
                Forms\Components\Select::make('cuenta_depreciacion_id')->label('Depreciación acumulada')->options($cuentas(['1.2']))->searchable(),
                Forms\Components\Select::make('cuenta_gasto_id')->label('Gasto por depreciación')->options($cuentas(['6', '5']))->searchable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $money = fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')->label('Código')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('nombre')->label('Activo')->searchable()
                    ->description(fn ($record) => $record->accountPlan?->name ?? 'sin cuenta contable'),
                Tables\Columns\TextColumn::make('fecha_compra')->label('Compra')->date('m/Y'),
                Tables\Columns\TextColumn::make('purchase_price')->label('Costo')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('vida_util_meses')->label('Vida útil')->alignEnd()
                    ->formatStateUsing(fn ($state) => round($state / 12, 1) . ' años'),
                Tables\Columns\TextColumn::make('cuota')->label('Deprec. mes')->alignEnd()
                    ->getStateUsing(fn ($record) => $money($record->cuota_mensual)),
                Tables\Columns\TextColumn::make('depreciacion_acumulada')->label('Acumulada')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('libros')->label('En libros')->alignEnd()->weight('bold')
                    ->getStateUsing(fn ($record) => $money($record->valor_en_libros)),
            ])
            ->actions([Tables\Actions\EditAction::make()->slideOver()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListActivosFijos::route('/')];
    }
}
