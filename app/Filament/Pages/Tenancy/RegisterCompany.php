<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;

class RegisterCompany extends RegisterTenant
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
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $company = Company::create($data);

        $company->users()->attach(auth()->user());

        auth()->user()->assignRole('admin');

        return $company;
    }
}
