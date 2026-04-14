<?php

namespace App\Filament\Logistics\Resources;

use App\Filament\Logistics\Resources\PackageResource\Pages;
use App\Models\Customer;
use App\Models\LogisticsBodega;
use App\Models\LogisticsPackage;
use App\Models\ServiceDesign;
use App\Models\ServicePackage;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
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

                        // 1. StoreCustomers con cuenta de portal
                        $portal = StoreCustomer::withoutGlobalScopes()
                            ->where('empresa_id', $empresaId)
                            ->where('activo', true)
                            ->where('is_super_admin', false)
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                'sc_' . $c->id => '★ ' . $c->nombre_completo . ' — ' . $c->email,
                            ]);

                        // 2. Clientes ERP sin cuenta portal aún (para poder seleccionarlos)
                        $linkedScIds = StoreCustomer::withoutGlobalScopes()
                            ->where('empresa_id', $empresaId)
                            ->whereNotNull('customer_id')
                            ->pluck('customer_id');

                        $erp = Customer::where('empresa_id', $empresaId)
                            ->where('activo', true)
                            ->whereNotIn('id', $linkedScIds)
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                'erp_' . $c->id => $c->nombre . ' — ' . ($c->numero_identificacion ?? 'ERP'),
                            ]);

                        return ['── Con portal ──' => $portal->toArray()]
                            + (($erp->isNotEmpty()) ? ['── Solo ERP ──' => $erp->toArray()] : []);
                    })
                    ->searchable()
                    ->nullable()
                    ->placeholder('Seleccionar cliente...')
                    ->dehydrateStateUsing(function ($state) use (&$erpCustomerId) {
                        // Si viene del ERP (erp_XX), crear StoreCustomer y devolver su id
                        if ($state && str_starts_with((string)$state, 'erp_')) {
                            $customerId = (int) str_replace('erp_', '', $state);
                            $customer   = Customer::find($customerId);
                            if ($customer) {
                                $sc = StoreCustomer::withoutGlobalScopes()
                                    ->where('empresa_id', $customer->empresa_id)
                                    ->where('customer_id', $customerId)
                                    ->first();

                                if (! $sc) {
                                    $sc = StoreCustomer::create([
                                        'empresa_id'   => $customer->empresa_id,
                                        'customer_id'  => $customerId,
                                        'tipo'         => $customer->tipo_persona === 'juridica' ? 'empresa' : 'persona',
                                        'razon_social' => $customer->tipo_persona === 'juridica' ? $customer->nombre : null,
                                        'nombre'       => $customer->nombre,
                                        'email'        => $customer->email ?? ('sin-correo-' . $customer->id . '@erp.local'),
                                        'cedula_ruc'   => $customer->numero_identificacion,
                                        'password'     => \Illuminate\Support\Facades\Hash::make(
                                            $customer->numero_identificacion ?? \Illuminate\Support\Str::random(12)
                                        ),
                                        'activo'       => true,
                                    ]);
                                }
                                return $sc->id;
                            }
                        }
                        // Si viene de portal (sc_XX o sin prefijo), devolver solo el número
                        return $state ? (int) str_replace('sc_', '', (string)$state) : null;
                    })
                    ->afterStateHydrated(function (Select $component, $state) {
                        // Al cargar el form en edición, asegurar que el valor tiene prefijo sc_
                        if ($state && ! str_starts_with((string)$state, 'sc_') && ! str_starts_with((string)$state, 'erp_')) {
                            $component->state('sc_' . $state);
                        }
                    })
                    ->helperText('★ = tiene acceso al portal del cliente. Los clientes ERP sin portal crearán cuenta automáticamente.')
                    ->createOptionForm([
                        Radio::make('tipo')
                            ->label('Tipo de cliente')
                            ->options(StoreCustomer::TIPOS)
                            ->default('persona')
                            ->inline()
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('razon_social')
                            ->label('Razón social')
                            ->required(fn (Get $get) => $get('tipo') === 'empresa')
                            ->visible(fn (Get $get) => $get('tipo') === 'empresa')
                            ->placeholder('Nombre de la empresa')
                            ->columnSpanFull(),

                        TextInput::make('nombre')
                            ->label(fn (Get $get) => $get('tipo') === 'empresa' ? 'Nombre del contacto' : 'Nombre')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('apellido')
                            ->label('Apellido')
                            ->nullable()
                            ->visible(fn (Get $get) => $get('tipo') !== 'empresa')
                            ->columnSpan(1),

                        TextInput::make('cedula_ruc')
                            ->label(fn (Get $get) => $get('tipo') === 'empresa' ? 'RUC' : 'Cédula / Pasaporte')
                            ->helperText('Se usará como contraseña inicial de acceso al portal.')
                            ->nullable()
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->nullable()
                            ->columnSpan(1),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $cedula = $data['cedula_ruc'] ?? null;
                        $customer = StoreCustomer::create([
                            'empresa_id'   => Filament::getTenant()->id,
                            'tipo'         => $data['tipo'] ?? 'persona',
                            'razon_social' => $data['razon_social'] ?? null,
                            'nombre'       => $data['nombre'],
                            'apellido'     => $data['apellido'] ?? null,
                            'email'        => $data['email'],
                            'telefono'     => $data['telefono'] ?? null,
                            'cedula_ruc'   => $cedula,
                            'password'     => Hash::make($cedula ?: Str::random(16)),
                            'activo'       => true,
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
                    ->label(function (Get $get) {
                        $pkg = ServicePackage::find($get('service_package_id'));
                        if ($pkg && $pkg->base_cobro === 'peso' && $pkg->unidad_cobro) {
                            return 'Peso (' . strtoupper($pkg->unidad_cobro) . ')';
                        }
                        return 'Peso (kg)';
                    })
                    ->suffix(function (Get $get) {
                        $pkg = ServicePackage::find($get('service_package_id'));
                        if ($pkg && $pkg->base_cobro === 'peso' && $pkg->unidad_cobro) {
                            return $pkg->unidad_cobro;
                        }
                        return 'kg';
                    })
                    ->helperText(function (Get $get) {
                        $pkg = ServicePackage::find($get('service_package_id'));
                        if ($pkg && $pkg->base_cobro === 'peso' && $pkg->unidad_cobro && $pkg->unidad_cobro !== 'kg') {
                            return 'Ingresa el peso en ' . strtoupper($pkg->unidad_cobro) . '. Se usará para calcular el cobro.';
                        }
                        return null;
                    })
                    ->numeric()
                    ->step(0.0001)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        $pkg = ServicePackage::find($get('service_package_id'));
                        if (! $pkg || $pkg->base_cobro !== 'peso') {
                            return;
                        }
                        $cantidad = (float) ($state ?? 0);
                        $set('cantidad_cobro', $cantidad);
                        if ($cantidad > 0) {
                            $set('monto_cobro', round((float) $pkg->precio_estimado * $cantidad, 2));
                        }
                    })
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

            // ── Servicio y cobro ──────────────────────────────────────────────
            Section::make('Servicio y cobro')
                ->description('Selecciona el servicio que aplica a este paquete y calcula el cobro.')
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Select::make('_service_design_id')
                        ->label('Diseño de servicio')
                        ->options(fn () => ServiceDesign::withoutGlobalScopes()
                            ->where('empresa_id', Filament::getTenant()->id)
                            ->where('activo', true)
                            ->pluck('nombre', 'id')
                        )
                        ->default(function (Get $get) {
                            $pkgId = $get('service_package_id');
                            if (! $pkgId) {
                                return null;
                            }
                            return ServicePackage::find($pkgId)?->service_design_id;
                        })
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('service_package_id', null);
                            $set('cantidad_cobro', null);
                            $set('monto_cobro', null);
                        })
                        ->dehydrated(false)
                        ->placeholder('Seleccionar diseño...')
                        ->columnSpan(2),

                    Select::make('service_package_id')
                        ->label('Tarifa / paquete')
                        ->options(function (Get $get) {
                            $designId = $get('_service_design_id');
                            if (! $designId) {
                                return [];
                            }
                            return ServicePackage::where('service_design_id', $designId)
                                ->where('activo', true)
                                ->get()
                                ->mapWithKeys(fn ($p) => [
                                    $p->id => $p->nombre
                                        . ' — $' . number_format($p->precio_estimado, 2)
                                        . ' / ' . ($p->unidad_cobro ?: $p->base_cobro),
                                ])
                                ->toArray();
                        })
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            $set('cantidad_cobro', null);
                            $set('monto_cobro', null);

                            if (! $state) {
                                return;
                            }
                            $pkg = ServicePackage::find($state);
                            if (! $pkg) {
                                return;
                            }

                            if ($pkg->base_cobro === 'fijo') {
                                $set('monto_cobro', (float) $pkg->precio_estimado);
                            } elseif ($pkg->base_cobro === 'peso') {
                                // Si ya hay peso ingresado, calcular de una vez
                                $cantidad = (float) ($get('peso_kg') ?? 0);
                                if ($cantidad > 0) {
                                    $set('cantidad_cobro', $cantidad);
                                    $set('monto_cobro', round((float) $pkg->precio_estimado * $cantidad, 2));
                                }
                            } elseif ($pkg->base_cobro === 'volumen') {
                                // Peso volumétrico: largo × ancho × alto / 5000
                                $largo = (float) ($get('largo_cm') ?? 0);
                                $ancho = (float) ($get('ancho_cm') ?? 0);
                                $alto  = (float) ($get('alto_cm') ?? 0);
                                if ($largo && $ancho && $alto) {
                                    $volumen = round($largo * $ancho * $alto / 5000, 4);
                                    $set('cantidad_cobro', $volumen);
                                    $set('monto_cobro', round((float) $pkg->precio_estimado * $volumen, 2));
                                }
                            }
                        })
                        ->disabled(fn (Get $get) => ! $get('_service_design_id'))
                        ->placeholder('Primero selecciona un diseño...')
                        ->columnSpan(2),

                    // Cantidad en la unidad del servicio (solo cuando NO es tarifa fija)
                    TextInput::make('cantidad_cobro')
                        ->label(function (Get $get) {
                            $pkg = ServicePackage::find($get('service_package_id'));
                            if (! $pkg || $pkg->base_cobro === 'fijo') {
                                return 'Cantidad';
                            }
                            $unidad = $pkg->unidad_cobro ?: $pkg->base_cobro;
                            return 'Cantidad (' . strtoupper($unidad) . ')';
                        })
                        ->suffix(function (Get $get) {
                            $pkg = ServicePackage::find($get('service_package_id'));
                            return $pkg?->unidad_cobro ?: null;
                        })
                        ->numeric()
                        ->step(0.0001)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            $pkg      = ServicePackage::find($get('service_package_id'));
                            $cantidad = (float) ($state ?? 0);
                            if ($pkg && $pkg->base_cobro !== 'fijo' && $cantidad > 0) {
                                $set('monto_cobro', round((float) $pkg->precio_estimado * $cantidad, 2));
                            }
                        })
                        ->visible(function (Get $get) {
                            $pkg = ServicePackage::find($get('service_package_id'));
                            // Para peso: se llena desde el campo en "Dimensiones". Para otros tipos variables sí se muestra aquí.
                            return $pkg && ! in_array($pkg->base_cobro, ['fijo', 'peso', 'volumen']);
                        })
                        ->columnSpan(1),

                    TextInput::make('monto_cobro')
                        ->label('Monto a cobrar')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->helperText(function (Get $get) {
                            $pkg = ServicePackage::find($get('service_package_id'));
                            if (! $pkg) {
                                return 'Se calcula al seleccionar el servicio.';
                            }
                            return match ($pkg->base_cobro) {
                                'fijo'  => 'Tarifa fija. Puedes ajustar manualmente.',
                                default => 'Calculado: $'
                                    . number_format($pkg->precio_estimado, 4)
                                    . ' × cantidad en ' . ($pkg->unidad_cobro ?: $pkg->base_cobro)
                                    . '. Puedes ajustar manualmente.',
                            };
                        })
                        ->visible(fn (Get $get) => (bool) $get('service_package_id'))
                        ->columnSpan(1),
                ])
                ->columns(4),

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
                TextColumn::make('monto_cobro')
                    ->label('Cobro')
                    ->money('USD')
                    ->placeholder('—')
                    ->sortable(),
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
