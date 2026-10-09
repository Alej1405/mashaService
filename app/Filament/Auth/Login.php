<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.auth.admin-login';

    /**
     * Quien ingresa por /admin se queda en /admin.
     *
     * Filament manda después del ingreso a la página que quedó pendiente en la
     * sesión. Si antes se abrió /app en ese navegador, el administrador
     * terminaba en /app, y la app instalada en el celular (que solo abarca
     * /admin) lo sacaba al navegador. Se descarta lo pendiente que no sea de
     * /admin; lo que sí lo es, se respeta.
     */
    public function authenticate(): ?LoginResponse
    {
        $pendiente = session('url.intended');
        $panel = url(filament()->getCurrentPanel()->getPath());

        if ($pendiente && ! str_starts_with($pendiente, $panel)) {
            session()->forget('url.intended');
        }

        return parent::authenticate();
    }

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
