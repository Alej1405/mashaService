<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $tenantRelationshipName = 'customers';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';

    // Clientes es TRANSVERSAL a cada empresa: no pertenece a un módulo/plan ni a un
    // panel concreto. Accesible para cualquier usuario del tenant, SOLO desde el
    // botón del hub de inicio (no aparece en la navegación de ningún panel).
    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getTenant() !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificación')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->readOnly()
                            ->placeholder('CLI-YYYY-XXXXX'),
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre Completo / Razón Social')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', \Illuminate\Support\Str::slug($state));
                                }
                            }),
                        Forms\Components\Select::make('tipo_persona')
                            ->label('Tipo de Persona')
                            ->options([
                                'natural' => 'Natural',
                                'juridica' => 'Jurídica',
                            ])
                            ->required()
                            ->default('natural'),
                        Forms\Components\Select::make('tipo_identificacion')
                            ->label('Tipo de Identificación')
                            ->options([
                                'cedula' => 'Cédula',
                                'ruc' => 'RUC',
                                'pasaporte' => 'Pasaporte',
                                'consumidor_final' => 'Consumidor Final',
                            ])
                            ->required()
                            ->default('ruc')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === 'consumidor_final') {
                                    $set('numero_identificacion', '9999999999999');
                                    $set('nombre', 'CONSUMIDOR FINAL');
                                    $set('tipo_persona', 'natural');
                                }
                            }),
                        Forms\Components\TextInput::make('numero_identificacion')
                            ->label('Número de Identificación')
                            ->required()
                            ->unique(ignorable: fn ($record) => $record)
                            ->maxLength(20),
                    ])->columns(2),

                Forms\Components\Section::make('Contacto y Ubicación')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Comercio Exterior')
                    ->schema([
                        Forms\Components\Toggle::make('es_exportador')
                            ->label('Es Exportador')
                            ->reactive()
                            ->default(false),
                        Forms\Components\TextInput::make('pais_destino')
                            ->label('País de Destino')
                            ->visible(fn (Forms\Get $get) => $get('es_exportador'))
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Contabilidad')
                    ->schema([
                        Forms\Components\Toggle::make('activo')
                            ->label('Cliente Activo')
                            ->default(true),
                    ])->columns(2),

                // ── El cliente ES el punto de venta: su landing pública ──
                Forms\Components\Section::make('Punto de venta (web)')
                    ->description('Publica al cliente como punto de venta y define su landing pública.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('publicado')
                            ->label('Publicar en la web')
                            ->helperText('Si está activo, el cliente aparece como punto de venta en la web.')
                            ->default(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->helperText('Identificador para la dirección pública. Se genera del nombre; puedes ajustarlo.')
                            ->maxLength(180)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('horario')
                            ->label('Horario de atención')
                            ->placeholder('Lun–Vie 9:00–18:00')
                            ->maxLength(180)
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('descripcion_web')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo')->image()->disk('public')->directory('clientes/logos')
                            ->columnSpan(1),
                        Forms\Components\FileUpload::make('banner')
                            ->label('Banner / portada')->image()->disk('public')->directory('clientes/banners')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('latitud')->label('Latitud')->numeric()->columnSpan(1),
                        Forms\Components\TextInput::make('longitud')->label('Longitud')->numeric()->columnSpan(1),
                    ])->columns(2),

                // ── Menú (tabla independiente), dependiente de la landing ──
                Forms\Components\Section::make('Menú del punto de venta')
                    ->description('Página de menú dependiente de la landing. Actívala solo si este cliente la necesita.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('menu_activo')
                            ->label('Activar menú')
                            ->helperText('Si está activo, la landing muestra la página de menú con estos productos.')
                            ->live()
                            ->default(false)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('menuItems')
                            ->relationship()
                            ->label('Productos del menú')
                            ->schema([
                                Forms\Components\TextInput::make('nombre')->label('Producto')->required()->maxLength(200)->columnSpan(2),
                                Forms\Components\TextInput::make('precio')->label('Precio')->numeric()->prefix('$')->required()->minValue(0)->columnSpan(1),
                                Forms\Components\Textarea::make('descripcion')->label('Detalle')->rows(2)->columnSpanFull(),
                                Forms\Components\FileUpload::make('imagen')->label('Imagen')->image()->disk('public')->directory('clientes/menu')->columnSpan(1),
                                Forms\Components\TextInput::make('orden')->label('Orden')->numeric()->default(0)->columnSpan(1),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? 'Nuevo ítem')
                            ->addActionLabel('Agregar producto')
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('menu_activo')),
                    ])->columns(2),

                // ── QR a la landing/menú del punto de venta ──
                Forms\Components\Section::make('Código QR')
                    ->description('Lleva a la landing (y al menú si está activo) de este punto de venta.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('qr')
                            ->label('')
                            ->content(fn (?Customer $record): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                                $record && $record->publicado && $record->slug
                                    ? '<div style="display:flex;flex-direction:column;gap:.5rem;align-items:flex-start">'
                                        . $record->qrSvg(200)
                                        . '<a href="' . e($record->landingUrl()) . '" target="_blank" style="color:#b45309;font-weight:600;font-size:.85rem">' . e($record->landingUrl()) . '</a></div>'
                                    : '<span style="color:#64748b">Publica el cliente y define su slug para generar el QR.</span>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_identificacion')
                    ->label('Identificación')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado Activo'),
                Tables\Filters\SelectFilter::make('tipo_persona')
                    ->label('Tipo Persona')
                    ->options([
                        'natural' => 'Natural',
                        'juridica' => 'Jurídica',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getQuickCreateFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre Completo / Razón Social')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('tipo_persona')
                ->label('Tipo de Persona')
                ->options([
                    'natural' => 'Natural',
                    'juridica' => 'Jurídica',
                ])
                ->required()
                ->default('natural'),
            Forms\Components\Select::make('tipo_identificacion')
                ->label('Tipo de Identificación')
                ->options([
                    'cedula' => 'Cédula',
                    'ruc' => 'RUC',
                    'pasaporte' => 'Pasaporte',
                    'consumidor_final' => 'Consumidor Final',
                ])
                ->required()
                ->default('ruc'),
            Forms\Components\TextInput::make('numero_identificacion')
                ->label('Número de Identificación')
                ->required()
                ->maxLength(20),
        ];
    }
}
