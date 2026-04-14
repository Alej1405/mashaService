<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreCustomerResource\Pages;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class StoreCustomerResource extends Resource
{
    protected static ?string $model = StoreCustomer::class;

    protected static ?string $tenantRelationshipName = 'storeCustomers';

    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationLabel  = 'Clientes';
    protected static ?string $navigationGroup  = 'E-Commerce';
    protected static ?string $modelLabel       = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int    $navigationSort   = 4;

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
            Section::make('Tipo de cliente')->schema([
                Radio::make('tipo')
                    ->label('')
                    ->options(\App\Models\StoreCustomer::TIPOS)
                    ->default('persona')
                    ->inline()
                    ->live()
                    ->columnSpanFull(),
            ]),

            Section::make('Datos del cliente')->schema([
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
                    ->helperText('Se usa como contraseña inicial de acceso al portal.')
                    ->nullable()
                    ->columnSpan(1),

                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->columnSpan(1),

                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->nullable()
                    ->columnSpan(1),

                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpan(1),

                Toggle::make('is_super_admin')
                    ->label('Super Admin del portal')
                    ->default(false)
                    ->columnSpan(1),
            ])->columns(2),

            Section::make('Cambiar contraseña')
                ->description('La contraseña inicial es la cédula / RUC. Usa esta sección solo si necesitas cambiarla.')
                ->collapsed()
                ->visibleOn('edit')
                ->schema([
                    TextInput::make('password')
                        ->label('Nueva contraseña')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Dejar vacío para no cambiar.')
                        ->columnSpan(1),
                    TextInput::make('password_confirmation')
                        ->label('Confirmar contraseña')
                        ->password()
                        ->revealable()
                        ->same('password')
                        ->dehydrated(false)
                        ->columnSpan(1),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_completo')
                    ->label('Cliente')
                    ->getStateUsing(fn ($record) => $record->nombre_completo)
                    ->description(fn ($record) => $record->tipo === 'empresa'
                        ? 'Empresa · ' . ($record->cedula_ruc ?? 'Sin RUC')
                        : 'Persona · ' . ($record->cedula_ruc ?? 'Sin cédula'))
                    ->searchable(['nombre', 'apellido', 'razon_social'])
                    ->sortable('nombre'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Órdenes')
                    ->badge()
                    ->color('info'),
                TextColumn::make('serviceContracts_count')
                    ->counts('serviceContracts')
                    ->label('Contratos')
                    ->badge()
                    ->color('success'),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                IconColumn::make('is_super_admin')
                    ->label('Super Admin')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('portal')
                    ->label('Ver portal')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn ($record) => route('portal.login', [
                        'slug' => Filament::getTenant()->slug,
                    ]))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStoreCustomers::route('/'),
            'create' => Pages\CreateStoreCustomer::route('/create'),
            'edit'   => Pages\EditStoreCustomer::route('/{record}/edit'),
        ];
    }
}
