<?php

namespace App\Filament\Auth;

use App\Mail\SolicitudAccesoMail;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Ingreso de los usuarios del ERP.
 *
 * Dos caras sobre el mismo lienzo, como en el diseño: el acceso y la solicitud
 * de acceso. El ERP no tiene registro abierto —un usuario pertenece a una
 * empresa y a un plan—, así que "crear una cuenta" no crea nada: manda un
 * correo para que alguien de MashaCorp termine la configuración a mano.
 *
 * El login de /admin sigue con su propia clase y su propia vista.
 */
class LoginUsuarios extends BaseLogin
{
    protected static string $view = 'filament.auth.login-usuarios';

    /** @var array<string, mixed> */
    public ?array $datosSolicitud = [];

    /** Se muestra el modal de confirmación. */
    public bool $solicitudEnviada = false;

    public function mount(): void
    {
        parent::mount();

        $this->solicitudForm->fill();
    }

    protected function getRedirectUrl(): string
    {
        return filament()->getHomeUrl();
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->label('Correo')
            ->placeholder('ejemplo@correo.com')
            ->autocomplete('off');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Contraseña')
            ->placeholder('Ingresa tu contraseña')
            ->autocomplete('new-password');
    }

    /** @return array<string, Form> */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),

            'solicitudForm' => $this->makeForm()
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre y apellido')
                        ->placeholder('Pablo Revilla')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('empresa')
                        ->label('Empresa')
                        ->placeholder('Nombre de tu empresa')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('correo')
                        ->label('Correo')
                        ->placeholder('ejemplo@correo.com')
                        ->email()
                        ->required()
                        ->maxLength(150),
                    TextInput::make('telefono')
                        ->label('Teléfono')
                        ->placeholder('09…')
                        ->tel()
                        ->maxLength(30),
                    Textarea::make('mensaje')
                        ->label('¿Qué necesitas del ERP?')
                        ->placeholder('Cuéntanos en una línea para llegar preparados.')
                        ->rows(2)
                        ->maxLength(500),
                    // Trampa para robots: una persona nunca la llena.
                    TextInput::make('sitio_web')
                        ->label('Sitio web')
                        // Se esconde el campo entero, etiqueta incluida: si se
                        // oculta solo el input, el label queda a la vista.
                        ->extraFieldWrapperAttributes([
                            'style'       => 'position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden',
                            'aria-hidden' => 'true',
                        ])
                        ->extraInputAttributes(['tabindex' => '-1', 'autocomplete' => 'off'])
                        ->dehydrated(),
                ])
                ->statePath('datosSolicitud'),
        ];
    }

    /**
     * No crea usuario: avisa. Quien lo configure decide empresa, plan y rol.
     */
    public function solicitarAcceso(): void
    {
        $datos = $this->solicitudForm->getState();

        // Un robot que llena el campo oculto se va con el mismo mensaje de
        // siempre: no se le dice que fue detectado.
        if (! empty($datos['sitio_web'])) {
            $this->solicitudEnviada = true;

            return;
        }

        $clave = 'solicitud-acceso:' . request()->ip();

        if (RateLimiter::tooManyAttempts($clave, 3)) {
            $this->addError('datosSolicitud.correo', 'Ya enviaste varias solicitudes. Intenta de nuevo en una hora.');

            return;
        }

        RateLimiter::hit($clave, 3600);

        try {
            Mail::to(static::destinoDeSolicitudes())->send(new SolicitudAccesoMail(
                nombre:   $datos['nombre'],
                empresa:  $datos['empresa'],
                correo:   $datos['correo'],
                telefono: $datos['telefono'] ?? null,
                mensaje:  $datos['mensaje'] ?? null,
            ));
        } catch (\Throwable $e) {
            // Si el correo falla, la solicitud no se pierde en silencio.
            Log::error('Solicitud de acceso al ERP sin enviar', [
                'error' => $e->getMessage(),
                'datos' => \Illuminate\Support\Arr::only($datos, ['nombre', 'empresa', 'correo', 'telefono']),
            ]);
        }

        $this->solicitudEnviada = true;
        $this->solicitudForm->fill();
    }

    /** A dónde llega la solicitud. Con respaldo, para que nunca quede sin destino. */
    public static function destinoDeSolicitudes(): string
    {
        return config('mail.solicitudes_acceso')
            ?: config('mail.from.address')
            ?: 'alejandro@mashaec.net';
    }

    public function cerrarConfirmacion(): void
    {
        $this->solicitudEnviada = false;
    }
}
