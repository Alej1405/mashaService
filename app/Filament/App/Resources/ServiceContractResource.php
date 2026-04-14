<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ServiceContractResource\Pages;
use App\Models\ServiceContract;
use App\Models\ServiceDesign;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceContractResource extends Resource
{
    protected static ?string $model = ServiceContract::class;

    protected static ?string $tenantRelationshipName = 'serviceContracts';

    protected static ?string $navigationIcon   = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel  = 'Contratos de Servicio';
    protected static ?string $navigationGroup  = 'E-Commerce';
    protected static ?string $modelLabel       = 'Contrato';
    protected static ?string $pluralModelLabel = 'Contratos de Servicio';
    protected static ?int    $navigationSort   = 5;

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('store_customer_id')
                ->label('Cliente')
                ->required()
                ->searchable()
                ->options(function () {
                    $empresa = Filament::getTenant();
                    return StoreCustomer::withoutGlobalScopes()
                        ->where('empresa_id', $empresa->id)
                        ->where('activo', true)
                        ->get()
                        ->mapWithKeys(fn ($c) => [
                            $c->id => trim($c->nombre . ' ' . ($c->apellido ?? '')) . ' — ' . $c->email,
                        ]);
                })
                ->columnSpan(2),

            Select::make('service_design_id')
                ->label('Diseño de Servicio (opcional)')
                ->searchable()
                ->nullable()
                ->options(function () {
                    $empresa = Filament::getTenant();
                    return ServiceDesign::withoutGlobalScopes()
                        ->where('empresa_id', $empresa->id)
                        ->where('activo', true)
                        ->pluck('nombre', 'id');
                })
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $design = ServiceDesign::find($state);
                        if ($design) {
                            $set('nombre_servicio', $design->nombre);
                            $set('descripcion', $design->descripcion_servicio);
                        }
                    }
                })
                ->columnSpan(2),

            TextInput::make('nombre_servicio')
                ->label('Nombre del servicio')
                ->required()
                ->maxLength(255)
                ->columnSpan(2),

            Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable()
                ->columnSpan(2),

            DatePicker::make('fecha_inicio')
                ->label('Fecha de inicio')
                ->required()
                ->default(now())
                ->columnSpan(1),

            DatePicker::make('fecha_fin')
                ->label('Fecha de vencimiento')
                ->nullable()
                ->helperText('Dejar vacío para contratos indefinidos')
                ->columnSpan(1),

            Select::make('estado')
                ->label('Estado')
                ->required()
                ->options([
                    'activo'     => 'Activo',
                    'pausado'    => 'Pausado',
                    'finalizado' => 'Finalizado',
                ])
                ->default('activo')
                ->columnSpan(1),

            TextInput::make('precio')
                ->label('Precio acordado')
                ->numeric()
                ->prefix('$')
                ->nullable()
                ->columnSpan(1),

            Select::make('periodicidad')
                ->label('Periodicidad')
                ->nullable()
                ->options([
                    'único'     => 'Pago único',
                    'mensual'   => 'Mensual',
                    'trimestral'=> 'Trimestral',
                    'semestral' => 'Semestral',
                    'anual'     => 'Anual',
                ])
                ->columnSpan(1),

            Textarea::make('notas')
                ->label('Notas internas')
                ->rows(3)
                ->nullable()
                ->columnSpan(2),

        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) =>
                        trim($record->customer->nombre . ' ' . ($record->customer->apellido ?? '')))
                    ->searchable(['store_customers.nombre', 'store_customers.apellido'])
                    ->sortable(),
                TextColumn::make('nombre_servicio')
                    ->label('Servicio')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo'     => 'success',
                        'pausado'    => 'warning',
                        'finalizado' => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('precio')
                    ->label('Precio')
                    ->money('USD')
                    ->placeholder('—'),
                TextColumn::make('periodicidad')
                    ->label('Periodicidad')
                    ->placeholder('—'),
                TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->placeholder('Indefinido')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->options([
                        'activo'     => 'Activo',
                        'pausado'    => 'Pausado',
                        'finalizado' => 'Finalizado',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServiceContracts::route('/'),
            'create' => Pages\CreateServiceContract::route('/create'),
            'edit'   => Pages\EditServiceContract::route('/{record}/edit'),
        ];
    }
}
