<?php

namespace App\Filament\Ecommerce\Resources;

use App\Filament\Ecommerce\Resources\StoreCustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class StoreCustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $tenantRelationshipName = 'storeCustomers';
    protected static ?string $navigationIcon         = 'heroicon-o-users';
    protected static ?string $navigationLabel        = 'Clientes';
    protected static ?string $navigationGroup        = 'Clientes';
    protected static ?string $modelLabel             = 'Cliente';
    protected static ?string $pluralModelLabel       = 'Clientes';
    protected static ?int    $navigationSort         = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Tipo de cliente')->schema([
                Radio::make('tipo_persona')->label('')->options(Customer::TIPOS)->default('persona')
                    ->inline()->live()->columnSpanFull(),
            ]),
            Section::make('Datos del cliente')->schema([
                TextInput::make('razon_social')->label('Razón social')
                    ->required(fn (Get $get) => $get('tipo_persona') === 'juridica')
                    ->visible(fn (Get $get) => $get('tipo_persona') === 'juridica')
                    ->placeholder('Nombre de la empresa')->columnSpanFull(),
                TextInput::make('nombre')
                    ->label(fn (Get $get) => $get('tipo_persona') === 'juridica' ? 'Nombre del contacto' : 'Nombre')
                    ->required()->columnSpan(1),
                TextInput::make('apellido')->label('Apellido')->nullable()
                    ->visible(fn (Get $get) => $get('tipo_persona') !== 'juridica')->columnSpan(1),
                TextInput::make('numero_identificacion')
                    ->label(fn (Get $get) => $get('tipo_persona') === 'juridica' ? 'RUC' : 'Cédula / Pasaporte')
                    ->helperText('Se usa como contraseña inicial de acceso al portal.')->nullable()->columnSpan(1),
                TextInput::make('email')->label('Correo')->email()->required()->columnSpan(1),
                TextInput::make('telefono')->label('Teléfono')->nullable()->columnSpan(1),
                Toggle::make('activo')->label('Activo')->default(true)->columnSpan(1),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->weight('semibold')
                    ->formatStateUsing(fn ($record) => trim($record->nombre . ' ' . ($record->apellido ?? ''))),
                TextColumn::make('email')->label('Correo')->searchable()->copyable()->color('gray'),
                TextColumn::make('numero_identificacion')->label('ID')->color('gray'),
                TextColumn::make('tipo_persona')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => Customer::TIPOS[$state] ?? $state),
                IconColumn::make('activo')->label('Activo')->boolean(),
                TextColumn::make('created_at')->label('Registrado')->date('d/m/Y')->color('gray')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([TernaryFilter::make('activo')->label('Activo')])
            ->actions([
                Action::make('reset_password')->label('Reset contraseña')->icon('heroicon-o-key')->color('warning')
                    ->requiresConfirmation()->modalHeading('Restablecer contraseña')
                    ->modalDescription(fn (Customer $r) => "Se restablecerá la contraseña de {$r->nombre} a su número de identificación.")
                    ->action(function (Customer $record) {
                        $record->update(['password' => Hash::make($record->numero_identificacion ?? 'temporal123')]);
                        \Filament\Notifications\Notification::make()->title('Contraseña restablecida')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([\Filament\Tables\Actions\BulkActionGroup::make([\Filament\Tables\Actions\DeleteBulkAction::make()])]);
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
