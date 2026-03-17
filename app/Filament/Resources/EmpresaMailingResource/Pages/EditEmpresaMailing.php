<?php

namespace App\Filament\Resources\EmpresaMailingResource\Pages;

use App\Filament\Resources\EmpresaMailingResource;
use App\Services\MailingService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEmpresaMailing extends EditRecord
{
    protected static string $resource = EmpresaMailingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testEmail')
                ->label('Enviar correo de prueba')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    TextInput::make('email_destino')
                        ->label('Email de destino')
                        ->email()
                        ->required()
                        ->default(Auth::user()?->email)
                        ->helperText('Se enviará un correo de prueba a este destinatario.'),
                ])
                ->action(function (array $data) {
                    $result = (new MailingService($this->record))
                        ->sendTestEmail($data['email_destino']);

                    Notification::make()
                        ->title($result['success'] ? 'Correo enviado' : 'Error al enviar')
                        ->body($result['message'])
                        ->{$result['success'] ? 'success' : 'danger'}()
                        ->send();
                })
                ->visible(fn () => ! empty($this->record->mailgun_api_key)
                    && ! empty($this->record->mailgun_domain)),

            Action::make('verificar')
                ->label('Verificar conexión')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(function () {
                    $result = (new MailingService($this->record))->testConnection();

                    Notification::make()
                        ->title($result['success'] ? 'Conexión exitosa' : 'Error de conexión')
                        ->body($result['message'])
                        ->{$result['success'] ? 'success' : 'danger'}()
                        ->send();
                }),

            Action::make('limpiar')
                ->label('Limpiar credenciales')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Limpiar credenciales de mailing?')
                ->modalDescription('Se eliminarán las credenciales del servicio de correo de esta empresa. El envío de correos dejará de funcionar.')
                ->modalSubmitActionLabel('Sí, limpiar')
                ->action(function () {
                    $this->record->update([
                        'mailgun_api_key'    => null,
                        'mailgun_domain'     => null,
                        'mailgun_from_email' => null,
                        'mailgun_from_name'  => null,
                    ]);

                    Notification::make()
                        ->title('Credenciales eliminadas')
                        ->body('Las credenciales de mailing han sido removidas de esta empresa.')
                        ->warning()
                        ->send();

                    $this->fillForm();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Configuración de mailing guardada';
    }
}
