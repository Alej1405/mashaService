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

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';

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
