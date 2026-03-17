<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Empresa;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

class RegisterEmpresa extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Registrar empresa';
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre de la empresa')
                    ->required(),
                TextInput::make('email')
                    ->label('Correo de la empresa')
                    ->email()
                    ->required(),
                Select::make('tipo_persona')
                    ->label('Tipo de persona')
                    ->options([
                        'natural'   => 'Persona Natural',
                        'juridica'  => 'Persona Jurídica',
                    ])
                    ->required(),
                Select::make('tipo_identificacion')
                    ->label('Tipo de identificación')
                    ->options([
                        'ruc'       => 'RUC',
                        'cedula'    => 'Cédula de Identidad',
                        'pasaporte' => 'Pasaporte',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('numero_identificacion')
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
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->required(),
                Textarea::make('actividad_economica')
                    ->label('¿A qué se dedica la empresa?')
                    ->required()
                    ->rows(2),

                Select::make('plan')
                    ->label('Plan de suscripción')
                    ->options([
                        'basic'      => 'Basic — Dashboard Mailing',
                        'pro'        => 'Pro — ERP Completo',
                        'enterprise' => 'Enterprise — Todo incluido',
                    ])
                    ->default('pro')
                    ->required()
                    ->native(false)
                    ->helperText('Puedes cambiar el plan desde el panel de administración.'),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $empresa = Empresa::create($data);

        auth()->user()->update(['empresa_id' => $empresa->id]);

        auth()->user()->assignRole('admin');

        return $empresa;
    }
}
