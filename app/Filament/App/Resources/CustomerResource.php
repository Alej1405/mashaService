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
                    // Datos de comercio exterior normalizados en customer_export (1:1).
                    ->relationship('export')
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

                // ── Ficha pública: solo el permiso. El contenido (descripción,
                //    horario, ubicación) lo edita el propio cliente en su portal. ──
                Forms\Components\Section::make('Ficha pública')
                    ->description('Si está habilitada, el cliente edita su información desde el portal y su ficha se muestra en la web.')
                    ->schema([
                        Forms\Components\Toggle::make('publicado')
                            ->label('Habilitar ficha pública')
                            ->helperText('El cliente podrá editar su descripción, horario y ubicación.')
                            ->default(false),
                    ])->columns(2),

                // Es la MISMA fila de customer_web que el cliente edita en su portal:
                // no hay copia ni versión nuestra. Lo que se escriba aquí es lo que ve
                // el cliente, y lo que él escriba es lo que se ve aquí.
                Forms\Components\Section::make('Información del local')
                    ->description('Lo que se muestra en la web. El cliente edita estos mismos campos desde su portal: hay un solo dato.')
                    ->relationship('web')
                    ->schema([
                        Forms\Components\Textarea::make('descripcion_web')
                            ->label('De qué se trata el local')
                            ->rows(3)->maxLength(2000)->columnSpanFull(),
                        Forms\Components\TextInput::make('horario')
                            ->label('Horario de atención')
                            ->placeholder('Lun a Vie 9:00 a 18:00')
                            ->maxLength(180),
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo')->image()->maxSize(2048)->disk('public')->directory('clientes/logos')
                            ->imagePreviewHeight('80'),
                        Forms\Components\TextInput::make('google_maps_url')
                            ->label('Enlace de Google Maps')
                            ->url()->maxLength(500)->columnSpanFull(),
                        Forms\Components\TextInput::make('latitud')
                            ->label('Latitud')->numeric()->minValue(-90)->maxValue(90),
                        Forms\Components\TextInput::make('longitud')
                            ->label('Longitud')->numeric()->minValue(-180)->maxValue(180),
                    ])->columns(2),
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
