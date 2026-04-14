<?php

namespace App\Filament\Logistics\Resources;

use App\Filament\Logistics\Resources\PackageResource\Pages;
use App\Models\LogisticsBodega;
use App\Models\LogisticsPackage;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PackageResource extends Resource
{
    protected static ?string $model                  = LogisticsPackage::class;
    protected static ?string $tenantRelationshipName = 'logisticsPackages';
    protected static ?string $navigationIcon         = 'heroicon-o-cube';
    protected static ?string $navigationLabel        = 'Paquetes';
    protected static ?string $navigationGroup        = 'Importaciones';
    protected static ?string $modelLabel             = 'Paquete';
    protected static ?string $pluralModelLabel       = 'Paquetes';
    protected static ?int    $navigationSort         = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Identificación')->schema([
                TextInput::make('numero_tracking')
                    ->label('Número de tracking')
                    ->placeholder('1Z999AA10123456784')
                    ->columnSpan(1),
                TextInput::make('referencia')
                    ->label('Referencia interna')
                    ->columnSpan(1),
                Textarea::make('descripcion')
                    ->label('Descripción del contenido')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Cliente')->schema([
                Select::make('store_customer_id')
                    ->label('Cliente')
                    ->options(function () {
                        $empresaId = Filament::getTenant()->id;
                        return StoreCustomer::withoutGlobalScopes()
                            ->where('empresa_id', $empresaId)
                            ->where('activo', true)
                            ->where('is_super_admin', false)
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                $c->id => trim($c->nombre . ' ' . ($c->apellido ?? ''))
                                    . ' — ' . $c->email,
                            ]);
                    })
                    ->searchable()
                    ->nullable()
                    ->placeholder('Seleccionar cliente...')
                    ->helperText(function () {
                        $count = StoreCustomer::withoutGlobalScopes()
                            ->where('empresa_id', Filament::getTenant()->id)
                            ->where('activo', true)
                            ->where('is_super_admin', false)
                            ->count();
                        return $count === 0
                            ? '⚠️ No hay clientes registrados. Usa el botón "Crear cliente" para agregar uno.'
                            : null;
                    })
                    ->createOptionForm([
                        TextInput::make('nombre')->label('Nombre')->required(),
                        TextInput::make('apellido')->label('Apellido')->nullable(),
                        TextInput::make('email')->label('Correo electrónico')->email()->required(),
                        TextInput::make('telefono')->label('Teléfono')->nullable(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $customer = StoreCustomer::create([
                            'empresa_id' => Filament::getTenant()->id,
                            'nombre'     => $data['nombre'],
                            'apellido'   => $data['apellido'] ?? null,
                            'email'      => $data['email'],
                            'telefono'   => $data['telefono'] ?? null,
                            'password'   => Hash::make(Str::random(16)),
                            'activo'     => true,
                        ]);
                        return $customer->id;
                    })
                    ->createOptionModalHeading('Registrar nuevo cliente')
                    ->columnSpanFull(),
            ]),

            Section::make('Origen')->schema([
                Select::make('bodega_id')
                    ->label('Bodega de origen')
                    ->options(fn () => LogisticsBodega::withoutGlobalScopes()
                        ->where('empresa_id', Filament::getTenant()->id)
                        ->pluck('nombre', 'id'))
                    ->required()
                    ->searchable()
                    ->columnSpan(1),
                DatePicker::make('fecha_recepcion_bodega')
                    ->label('Fecha recepción en bodega')
                    ->columnSpan(1),
            ])->columns(2),

            Section::make('Dimensiones y valor')->schema([
                TextInput::make('peso_kg')
                    ->label('Peso (kg)')
                    ->numeric()
                    ->step(0.001)
                    ->suffix('kg')
                    ->columnSpan(1),
                Grid::make(3)->schema([
                    TextInput::make('largo_cm')->label('Largo (cm)')->numeric()->step(0.01)->suffix('cm'),
                    TextInput::make('ancho_cm')->label('Ancho (cm)')->numeric()->step(0.01)->suffix('cm'),
                    TextInput::make('alto_cm')->label('Alto (cm)')->numeric()->step(0.01)->suffix('cm'),
                ])->columnSpan(2),
                TextInput::make('valor_declarado')
                    ->label('Valor declarado')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->columnSpan(1),
                Select::make('moneda')
                    ->label('Moneda')
                    ->options(['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'])
                    ->default('USD')
                    ->columnSpan(1),
            ])->columns(3),

            Section::make('Notas')->collapsed()->schema([
                Textarea::make('notas')->label('Notas')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_tracking')
                    ->label('Tracking')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('storeCustomer.nombre')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => $record->storeCustomer
                        ? trim($record->storeCustomer->nombre . ' ' . ($record->storeCustomer->apellido ?? ''))
                        : '—')
                    ->placeholder('—'),
                TextColumn::make('bodega.pais')
                    ->label('Origen')
                    ->badge()
                    ->color(fn (string $state) => $state === 'EEUU' ? 'info' : 'warning'),
                TextColumn::make('peso_kg')
                    ->label('Peso')
                    ->suffix(' kg')
                    ->placeholder('—'),
                TextColumn::make('valor_declarado')
                    ->label('Valor declarado')
                    ->money('USD'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => LogisticsPackage::ESTADOS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'registrado' => 'gray',
                        'en_bodega'  => 'info',
                        'asignado'   => 'warning',
                        'entregado'  => 'success',
                        default      => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('store_customer_id')
                    ->label('Cliente')
                    ->options(function () {
                        return StoreCustomer::withoutGlobalScopes()
                            ->where('empresa_id', Filament::getTenant()->id)
                            ->where('activo', true)
                            ->where('is_super_admin', false)
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                $c->id => trim($c->nombre . ' ' . ($c->apellido ?? '')),
                            ]);
                    })
                    ->searchable(),
                SelectFilter::make('bodega_id')
                    ->label('Bodega')
                    ->relationship('bodega', 'nombre'),
                SelectFilter::make('estado')
                    ->options(LogisticsPackage::ESTADOS),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit'   => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
