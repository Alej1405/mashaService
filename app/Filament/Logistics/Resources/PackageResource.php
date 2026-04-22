<?php

namespace App\Filament\Logistics\Resources;

use App\Filament\Logistics\Resources\PackageResource\Pages;
use App\Mail\LogisticsBillingApprovedMail;
use App\Models\Customer;
use App\Models\Empresa;
use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsBodega;
use App\Models\LogisticsPackage;
use App\Models\ServiceDesign;
use App\Models\ServicePackage;
use App\Models\StoreCustomer;
use App\Models\StoreCustomerCompany;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Support\HtmlString;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Laravel\Facades\Resend;

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
            ])->columns(['default' => 1, 'sm' => 2]),

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
            ])->columns(['default' => 1, 'sm' => 2]),

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
                        $pkg    = ServicePackage::find($get('service_package_id'));
                        $pesoKg = (float) ($state ?? 0);

                        if ($pkg && $pkg->base_cobro === 'peso') {
                            $set('cantidad_cobro', $pesoKg);
                            if ($pesoKg > 0) {
                                $set('monto_cobro', round((float) $pkg->precio_estimado * $pesoKg, 2));
                            }
                        }

                        // Recalcular cargos con base peso al cambiar el peso del paquete
                        if ($pkg && $pesoKg > 0) {
                            foreach ([
                                ['cobro' => 'cobro_nacionalizacion',    'tipo' => 'cobro_nacionalizacion_tipo'],
                                ['cobro' => 'cobro_transporte_interno', 'tipo' => 'cobro_transporte_interno_tipo'],
                                ['cobro' => 'cobro_otro',               'tipo' => 'cobro_otro_tipo'],
                            ] as $cfg) {
                                $valor = (float) ($pkg->{$cfg['cobro']} ?? 0);
                                if ($valor > 0 && ($pkg->{$cfg['tipo']} ?? 'tramite') === 'peso') {
                                    $set($cfg['cobro'], round($valor * $pesoKg, 2));
                                }
                            }
                        }
                    })
                    ->columnSpan(1),
                Grid::make(['default' => 1, 'sm' => 3])->schema([
                    TextInput::make('largo_cm')->label('Largo (cm)')->numeric()->step(0.01)->suffix('cm'),
                    TextInput::make('ancho_cm')->label('Ancho (cm)')->numeric()->step(0.01)->suffix('cm'),
                    TextInput::make('alto_cm')->label('Alto (cm)')->numeric()->step(0.01)->suffix('cm'),
                ])->columnSpan(['default' => 1, 'sm' => 2]),
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
                TextInput::make('gastos_envio')
                    ->label('Gastos de envío ($)')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->nullable()
                    ->columnSpan(1),
                TextInput::make('impuestos_amazon')
                    ->label('Impuestos de origen ($)')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->nullable()
                    ->helperText('Impuestos cobrados en el país de origen.')
                    ->columnSpan(1),
                TextInput::make('impuestos_aduana')
                    ->label('Impuestos de aduana / liquidación ($)')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->nullable()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if (! $get('impuestos_paga_empresa')) {
                            return;
                        }
                        // Si el toggle está activo, recalcular monto incluyendo nuevos impuestos
                        $monto     = (float) ($get('monto_cobro') ?? 0);
                        $anterior  = (float) ($get('_impuestos_aduana_prev') ?? 0);
                        $nuevo     = (float) ($state ?? 0);
                        $set('monto_cobro', round(max(0, $monto - $anterior + $nuevo), 2));
                    })
                    ->helperText('Impuestos generados en aduana por la liquidación.')
                    ->columnSpan(1),
                Toggle::make('impuestos_paga_empresa')
                    ->label('¿Los impuestos de aduana los paga la empresa?')
                    ->helperText('Al activar, los impuestos de aduana / liquidación se suman al monto a cobrar.')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, bool $state) {
                        $impuestos = (float) ($get('impuestos_aduana') ?? 0);
                        if ($impuestos <= 0) {
                            return;
                        }
                        $monto = (float) ($get('monto_cobro') ?? 0);
                        if ($state) {
                            $set('monto_cobro', round($monto + $impuestos, 2));
                        } else {
                            $set('monto_cobro', round(max(0, $monto - $impuestos), 2));
                        }
                    })
                    ->columnSpan(['default' => 1, 'sm' => 2]),
            ])->columns(['default' => 1, 'sm' => 2, 'md' => 3]),

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

                            // Auto-fill cargos adicionales desde el diseño de servicio
                            $pesoKg = (float) ($get('peso_kg') ?? 0);
                            foreach ([
                                ['cobro' => 'cobro_nacionalizacion',    'tipo' => 'cobro_nacionalizacion_tipo'],
                                ['cobro' => 'cobro_transporte_interno', 'tipo' => 'cobro_transporte_interno_tipo'],
                                ['cobro' => 'cobro_otro',               'tipo' => 'cobro_otro_tipo'],
                            ] as $cfg) {
                                $valor = (float) ($pkg->{$cfg['cobro']} ?? 0);
                                if ($valor <= 0) {
                                    continue;
                                }
                                $tipo = $pkg->{$cfg['tipo']} ?? 'tramite';
                                $set($cfg['cobro'], $tipo === 'peso' && $pesoKg > 0
                                    ? round($valor * $pesoKg, 2)
                                    : $valor);
                            }
                            if ($pkg->cobro_otro_descripcion) {
                                $set('cobro_otro_descripcion', $pkg->cobro_otro_descripcion);
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
                        ->label('Monto a cobrar (servicio)')
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
                ->columns(['default' => 1, 'sm' => 2, 'md' => 4]),

            Section::make('Cargos adicionales')
                ->description('Cargos extras que se incluirán en la factura del cliente (IVA según tipo).')
                ->icon('heroicon-o-plus-circle')
                ->collapsed()
                ->schema([
                    TextInput::make('cobro_nacionalizacion')
                        ->label('Nacionalización ($)')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->nullable()
                        ->helperText('Costos de trámite de nacionalización — se factura con 15% IVA.')
                        ->columnSpan(1),
                    TextInput::make('cobro_transporte_interno')
                        ->label('Transporte interno ($)')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->nullable()
                        ->helperText('Flete / transporte dentro del país — se factura con 15% IVA.')
                        ->columnSpan(1),
                    TextInput::make('cobro_otro')
                        ->label('Otro cargo ($)')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->nullable()
                        ->helperText('Cargo adicional — se factura con 15% IVA.')
                        ->columnSpan(1),
                    TextInput::make('cobro_otro_descripcion')
                        ->label('Descripción del otro cargo')
                        ->placeholder('Ej. Almacenaje, seguro, manejo...')
                        ->nullable()
                        ->visible(fn (Get $get) => (float)($get('cobro_otro') ?? 0) > 0)
                        ->columnSpan(1),
                ])->columns(['default' => 1, 'sm' => 2, 'md' => 4]),

            Section::make('Estado')->schema([
                Select::make('estado')
                    ->label('Estado principal')
                    ->options(fn () => collect(LogisticsPackage::ESTADOS)
                        ->mapWithKeys(fn ($v, $k) => [$k => $v['label']]))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('estado_secundario', null)),

                Select::make('estado_secundario')
                    ->label('Estado secundario')
                    ->options(fn (Get $get) => collect(LogisticsPackage::ESTADOS_SECUNDARIOS[$get('estado')] ?? [])
                        ->mapWithKeys(fn ($v, $k) => [$k => $v['label']]))
                    ->placeholder('Sin estado secundario')
                    ->nullable()
                    ->visible(fn (Get $get) => ! empty(LogisticsPackage::ESTADOS_SECUNDARIOS[$get('estado')])),
            ])->columns(['default' => 1, 'sm' => 2])->visibleOn('edit'),

            Section::make('Productos del paquete')
                ->description('Artículos que contiene este paquete. Solo informativo para el cliente.')
                ->icon('heroicon-o-shopping-bag')
                ->collapsed()
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            TextInput::make('nombre')
                                ->label('Nombre del producto')
                                ->required()
                                ->columnSpan(['default' => 2, 'sm' => 3]),
                            TextInput::make('valor')
                                ->label('Valor ($)')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->nullable()
                                ->columnSpan(1),
                            FileUpload::make('foto_path')
                                ->label('Foto')
                                ->image()
                                ->disk('public')
                                ->directory('package-items')
                                ->imageEditor()
                                ->nullable()
                                ->columnSpan(['default' => 2, 'sm' => 2]),
                            Textarea::make('descripcion')
                                ->label('Descripción')
                                ->rows(2)
                                ->nullable()
                                ->columnSpan(['default' => 2, 'sm' => 2]),
                        ])
                        ->columns(['default' => 2, 'sm' => 4])
                        ->addActionLabel('Agregar producto')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null),
                ]),

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
                    ->formatStateUsing(function ($state, $record) {
                        $label = LogisticsPackage::ESTADOS[$state]['label'] ?? $state;
                        if ($record->estado_secundario) {
                            $sec = LogisticsPackage::ESTADOS_SECUNDARIOS[$state][$record->estado_secundario] ?? null;
                            if ($sec) {
                                $label .= ' › ' . $sec['label'];
                            }
                        }
                        return $label;
                    })
                    ->color(fn ($state) => match ($state) {
                        'embarque_solicitado' => 'gray',
                        'registrado'          => 'info',
                        'en_aduana'           => 'warning',
                        'finalizado_aduana'   => 'primary',
                        'pago_servicios'      => 'danger',
                        'en_entrega'          => 'success',
                        default               => 'gray',
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
                    ->options(fn () => collect(LogisticsPackage::ESTADOS)
                        ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])),
            ])
            ->actions([
                // ── Aprobar valores de facturación ────────────────────────────
                Action::make('aprobar_valores')
                    ->label('Aprobar valores')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(function (LogisticsPackage $record): bool {
                        return LogisticsBillingRequest::where('package_id', $record->id)
                            ->where('estado', 'pendiente')
                            ->exists();
                    })
                    ->modalHeading('Aprobar valores del cliente')
                    ->modalDescription('Se enviará un correo al cliente notificando que los valores han sido aprobados.')
                    ->modalWidth('lg')
                    ->form(function (LogisticsPackage $record): array {
                        $billing = LogisticsBillingRequest::where('package_id', $record->id)
                            ->where('estado', 'pendiente')
                            ->with('storeCustomer')
                            ->first();

                        $resumen = $billing
                            ? new HtmlString(
                                '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;font-size:13px;line-height:1.8;">'
                                . '<strong>Nota de venta:</strong> ' . e($billing->numero_nota_venta)
                                . ' &nbsp;|&nbsp; <strong>Cliente:</strong> ' . e($billing->storeCustomer?->nombre_completo ?? '—')
                                . '<br><strong>Subtotal 15%:</strong> $' . number_format($billing->subtotal_15, 2)
                                . ($billing->descuento_monto > 0
                                    ? ' &nbsp;|&nbsp; <strong style="color:#16a34a;">Descuento (' . match($billing->descuento_tipo) {
                                        'cliente_fijo' => 'cliente fijo',
                                        'promocion'    => 'promoción',
                                        default        => $billing->descuento_descripcion ?? 'otro',
                                    } . '):</strong> <span style="color:#16a34a;">− $' . number_format($billing->descuento_monto, 2) . '</span>'
                                    : '')
                                . '<br><strong>IVA 15%:</strong> $' . number_format($billing->iva, 2)
                                . ' &nbsp;|&nbsp; <strong>Total:</strong> $' . number_format($billing->total, 2)
                                . '</div>'
                            )
                            : new HtmlString('<p style="color:#ef4444;">No se encontró solicitud pendiente.</p>');

                        $companiesOpts = [];
                        if ($billing) {
                            $companiesOpts['customer'] = 'A nombre del cliente ('
                                . trim(($billing->storeCustomer->nombre ?? '') . ' ' . ($billing->storeCustomer->apellido ?? ''))
                                . ')';
                            $companies = StoreCustomerCompany::where('store_customer_id', $billing->store_customer_id)
                                ->where('empresa_id', Filament::getTenant()->id)
                                ->get();
                            foreach ($companies as $c) {
                                $companiesOpts['company_' . $c->id] = 'Empresa: ' . $c->nombre . ' (RUC ' . $c->ruc . ')';
                            }
                        }

                        return [
                            \Filament\Forms\Components\Placeholder::make('resumen')
                                ->label('Solicitud de facturación')
                                ->content($resumen),

                            Select::make('billing_type')
                                ->label('¿A nombre de quién se factura?')
                                ->options($companiesOpts)
                                ->required()
                                ->default('customer'),

                            Textarea::make('observacion')
                                ->label('Observación / cómo aprobó el cliente')
                                ->placeholder('Ej. El cliente llamó y confirmó los valores por teléfono.')
                                ->required()
                                ->rows(3),
                        ];
                    })
                    ->action(function (LogisticsPackage $record, array $data): void {
                        $billing = LogisticsBillingRequest::where('package_id', $record->id)
                            ->where('estado', 'pendiente')
                            ->with(['storeCustomer', 'package'])
                            ->first();

                        if (! $billing) {
                            Notification::make()->title('No se encontró solicitud pendiente')->warning()->send();
                            return;
                        }

                        // Resolver empresa / cliente para facturación
                        $billingType = $data['billing_type'];
                        $company     = null;
                        if (str_starts_with($billingType, 'company_')) {
                            $companyId   = (int) str_replace('company_', '', $billingType);
                            $company     = StoreCustomerCompany::find($companyId);
                            $billingType = 'company';
                        }

                        $billing->aceptar('erp', $billingType, $company, $billing->storeCustomer);
                        $billing->update(['notas' => $data['observacion']]);

                        // Enviar correo de confirmación por Resend
                        $customer = $billing->storeCustomer;
                        $empresa  = Empresa::find($record->empresa_id);

                        if ($customer && $empresa && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                            $mail = new LogisticsBillingApprovedMail($billing, $customer, $empresa, $data['observacion']);
                            try {
                                Resend::emails()->send([
                                    'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                                    'to'      => [$customer->email],
                                    'subject' => $mail->envelope()->subject,
                                    'html'    => $mail->buildHtml(),
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Error enviando correo de aprobación de facturación', [
                                    'billing_id' => $billing->id,
                                    'error'      => $e->getMessage(),
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Valores aprobados y cliente notificado')
                            ->success()
                            ->send();
                    }),

                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
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
