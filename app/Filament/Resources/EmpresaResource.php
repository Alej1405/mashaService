<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpresaResource\Pages;
use App\Filament\Resources\EmpresaResource\RelationManagers;
use App\Models\Empresa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class EmpresaResource extends Resource
{
    protected static ?string $model = Empresa::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $modelLabel = 'Empresa';
    protected static ?string $pluralModelLabel = 'Empresas';

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return $user && $user->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('URL Amigable (Slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrated(),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Forms\Components\Section::make('Identificación y Datos Legales')
                    ->schema([
                        Forms\Components\Select::make('tipo_persona')
                            ->label('Tipo de persona')
                            ->options([
                                'natural'  => 'Persona Natural',
                                'juridica' => 'Persona Jurídica',
                            ])
                            ->required(),
                        Forms\Components\Select::make('tipo_identificacion')
                            ->label('Tipo de identificación')
                            ->options([
                                'ruc'       => 'RUC',
                                'cedula'    => 'Cédula de Identidad',
                                'pasaporte' => 'Pasaporte',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('numero_identificacion')
                            ->label('Número de identificación')
                            ->required()
                            ->numeric()
                            ->minLength(fn (Get $get): int => match ($get('tipo_identificacion')) {
                                'ruc'    => 13,
                                'cedula' => 10,
                                default  => 1,
                            })
                            ->maxLength(fn (Get $get): int => match ($get('tipo_identificacion')) {
                                'ruc'    => 13,
                                'cedula' => 10,
                                default  => 20,
                            })
                            ->hint(fn (Get $get): string => match ($get('tipo_identificacion')) {
                                'ruc'    => '13 dígitos',
                                'cedula' => '10 dígitos',
                                default  => '',
                            }),
                        Forms\Components\TextInput::make('direccion')
                            ->label('Dirección')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('actividad_economica')
                            ->label('¿A qué se dedica la empresa?')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Plan de Suscripción')
                    ->description('Define el plan que determina las funcionalidades disponibles para esta empresa.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Select::make('plan')
                            ->label('Plan activo')
                            ->options([
                                'basic'      => 'Basic — Solo Dashboard Mailing',
                                'pro'        => 'Pro — ERP Completo',
                                'enterprise' => 'Enterprise — Todo incluido',
                            ])
                            ->default('pro')
                            ->required()
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Configuración de Módulos Operativos')
                    ->description('Active los módulos que correspondan al tipo de operación de la empresa.')
                    ->schema([
                        Forms\Components\Toggle::make('tipo_operacion_productos')
                            ->label('Operación Productos')
                            ->default(true),
                        Forms\Components\Toggle::make('tipo_operacion_servicios')
                            ->label('Operación Servicios'),
                        Forms\Components\Toggle::make('tipo_operacion_manufactura')
                            ->label('Operación Manufactura'),
                        Forms\Components\Toggle::make('tiene_logistica')
                            ->label('Módulo Logística'),
                        Forms\Components\Toggle::make('tiene_comercio_exterior')
                            ->label('Comercio Exterior'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('plan')
                    ->label('Plan')
                    ->colors([
                        'gray'    => 'basic',
                        'info'    => 'pro',
                        'warning' => 'enterprise',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'basic'      => 'Basic',
                        'pro'        => 'Pro',
                        'enterprise' => 'Enterprise',
                        default      => $state,
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('tipo_operacion_productos')
                    ->label('Prod.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tipo_operacion_servicios')
                    ->label('Serv.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tipo_operacion_manufactura')
                    ->label('Man.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tiene_logistica')
                    ->label('Log.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tiene_comercio_exterior')
                    ->label('ComExt.')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('tipo_operacion_productos')
                    ->label('Productos'),
                Tables\Filters\TernaryFilter::make('tiene_logistica')
                    ->label('Logística'),
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
            'index' => Pages\ListEmpresas::route('/'),
            'create' => Pages\CreateEmpresa::route('/create'),
            'edit' => Pages\EditEmpresa::route('/{record}/edit'),
        ];
    }
}
