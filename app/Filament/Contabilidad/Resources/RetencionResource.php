<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\RetencionResource\Pages;
use App\Models\PorcentajeRetencion;
use App\Models\Retencion;
use App\Services\ContabilidadService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Retenciones emitidas.
 *
 * El porcentaje no se escribe: se elige de la tabla vigente a la fecha del
 * comprobante. La retención no es costo, es menos caja para el proveedor.
 */
class RetencionResource extends Resource
{
    protected static ?string $model = Retencion::class;
    protected static ?string $slug = 'retenciones';
    protected static ?string $navigationIcon  = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Retenciones';
    protected static ?string $navigationGroup = 'Día a día';
    protected static ?string $modelLabel = 'retención';
    protected static ?string $pluralModelLabel = 'Retenciones';
    protected static ?int    $navigationSort = 3;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Comprobante')->columns(3)->schema([
                Forms\Components\TextInput::make('numero')->label('Número')->maxLength(30),
                Forms\Components\DatePicker::make('fecha')->label('Fecha')->default(now())->required()
                    ->helperText('Decide qué porcentajes se aplican.')->live(),
                Forms\Components\Select::make('supplier_id')->label('Proveedor')
                    ->relationship('supplier', 'nombre')->searchable(),
            ]),
            Forms\Components\Section::make('Base imponible')->columns(2)->schema([
                Forms\Components\TextInput::make('base_renta')->label('Base para renta')->numeric()->prefix('$')
                    ->helperText('El subtotal de la factura, sin IVA.')->default(0),
                Forms\Components\TextInput::make('base_iva')->label('IVA de la factura')->numeric()->prefix('$')
                    ->helperText('La retención de IVA se calcula sobre el IVA, no sobre la base.')->default(0),
            ]),
            Forms\Components\Section::make('Detalle')
                ->description('Cada línea toma su porcentaje de la tabla vigente a la fecha.')
                ->schema([
                    Forms\Components\Repeater::make('lineas')
                        ->relationship()
                        ->label('')
                        ->columns(4)
                        ->defaultItems(0)
                        ->schema([
                            Forms\Components\Select::make('porcentaje_retencion_id')
                                ->label('Concepto')
                                ->options(fn (Forms\Get $get) => PorcentajeRetencion::query()
                                    ->vigentesEn(now())
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [$p->id => strtoupper($p->tipo) . ' · ' . $p->concepto . ' (' . rtrim(rtrim(number_format($p->porcentaje, 2, ',', '.'), '0'), ',') . ' %)'])
                                    ->all())
                                ->searchable()->required()->live()
                                ->columnSpan(2)
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (! $p = PorcentajeRetencion::find($state)) return;
                                    $set('tipo', $p->tipo);
                                    $set('concepto', $p->concepto);
                                    $set('porcentaje', $p->porcentaje);
                                    $set('codigo_sri', $p->codigo_sri);
                                }),
                            Forms\Components\TextInput::make('base')->label('Base')->numeric()->prefix('$')->required()->live(),
                            Forms\Components\TextInput::make('valor')->label('Retenido')->numeric()->prefix('$')
                                ->readOnly()
                                ->afterStateHydrated(fn () => null),
                            Forms\Components\Hidden::make('tipo'),
                            Forms\Components\Hidden::make('concepto'),
                            Forms\Components\Hidden::make('porcentaje'),
                            Forms\Components\Hidden::make('codigo_sri'),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['valor'] = round(((float) $data['base']) * ((float) $data['porcentaje']) / 100, 2);
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['valor'] = round(((float) $data['base']) * ((float) $data['porcentaje']) / 100, 2);
                            return $data;
                        }),
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
                Tables\Columns\TextColumn::make('numero')->label('Número')->searchable()->placeholder('sin número'),
                Tables\Columns\TextColumn::make('supplier.nombre')->label('Proveedor')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('retenido_renta')->label('Renta')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('retenido_iva')->label('IVA')->alignEnd()->formatStateUsing($money),
                Tables\Columns\TextColumn::make('total_retenido')->label('Total')->alignEnd()->weight('bold')
                    ->formatStateUsing($money)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->formatStateUsing($money)),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()->slideOver()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRetenciones::route('/'),
            'create' => Pages\CrearRetencion::route('/nueva'),
            'edit'   => Pages\EditarRetencion::route('/{record}/editar'),
        ];
    }
}
