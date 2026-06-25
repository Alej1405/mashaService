<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.auth.admin-login';

    protected function getRedirectUrl(): string
    {
        return filament()->getHomeUrl();
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->autocomplete('off');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->autocomplete('new-password');
    }
}
