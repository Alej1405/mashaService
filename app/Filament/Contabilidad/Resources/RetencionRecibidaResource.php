<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\RetencionRecibidaResource\Pages;
use App\Models\PorcentajeRetencion;
use App\Models\RetencionRecibida;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Retenciones que nos practican los clientes.
 *
 * No son gasto: son impuesto pagado por anticipado que se resta del impuesto
 * causado en el 101. Sin registrarlas, la empresa paga dos veces.
 */
class RetencionRecibidaResource extends Resource
{
    protected static ?string $model = RetencionRecibida::class;
    protected static ?string $slug = 'retenciones-recibidas';
    protected static ?string $navigationIcon  = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Retenciones recibidas';
    protected static ?string $modelLabel = 'retención recibida';
    protected static ?string $pluralModelLabel = 'Retenciones recibidas';
    protected static ?int    $navigationSort = 4;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\TextInput::make('numero')->label('Número del comprobante')->maxLength(30),
            Forms\Components\DatePicker::make('fecha')->label('Fecha')->default(now())->required(),
            Forms\Components\Select::make('customer_id')->label('Cliente que retuvo')
                ->relationship('customer', 'nombre')->searchable()->preload(),
            Forms\Components\Select::make('tipo')->label('Tipo')->required()->live()
                ->options(['renta' => 'Impuesto a la renta', 'iva' => 'IVA']),
            Forms\Components\Select::make('concepto')->label('Concepto')
                ->options(fn (Forms\Get $get) => PorcentajeRetencion::query()
                    ->where('tipo', $get('tipo') ?: 'renta')->vigentesEn(now())
                    ->pluck('concepto', 'concepto')->all())
                ->searchable()->live()
                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                    if ($p = PorcentajeRetencion::where('concepto', $state)->where('tipo', $get('tipo'))->first()) {
                        $set('porcentaje', $p->porcentaje);
                        $set('codigo_sri', $p->codigo_sri);
                        $set('valor', round(((float) $get('base')) * ((float) $p->porcentaje) / 100, 2));
                    }
                })
                ->columnSpan(2),
            Forms\Components\TextInput::make('base')->label('Base')->numeric()->prefix('$')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) =>
                    $set('valor', round(((float) $state) * ((float) $get('porcentaje')) / 100, 2))),
            Forms\Components\TextInput::make('porcentaje')->label('Porcentaje')->numeric()->suffix('%')->required(),
            Forms\Components\TextInput::make('valor')->label('Retenido')->numeric()->prefix('$')->required(),
            Forms\Components\TextInput::make('codigo_sri')->label('Código SRI')->maxLength(10)
                ->helperText('Se completa cuando llegue la ficha del SRI'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $money = fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.');

        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('numero')->label('Comprobante')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('customer.nombre')->label('Cliente')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'iva' ? 'IVA' : 'Renta')
                    ->color(fn (?string $state) => $state === 'iva' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('concepto')->label('Concepto')->wrap()->placeholder('—'),
                Tables\Columns\TextColumn::make('base')->label('Base')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('porcentaje')->label('%')->alignEnd()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',') . ' %'),
                Tables\Columns\TextColumn::make('valor')->label('Retenido')->alignEnd()->weight('bold')->formatStateUsing($money)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Crédito del periodo')->formatStateUsing($money)),
            ])
            ->actions([Tables\Actions\EditAction::make()->slideOver()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRetencionesRecibidas::route('/')];
    }
}
