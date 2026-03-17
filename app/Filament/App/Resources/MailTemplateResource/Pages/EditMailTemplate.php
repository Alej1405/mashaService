<?php

namespace App\Filament\App\Resources\MailTemplateResource\Pages;

use App\Filament\App\Resources\MailTemplateResource;
use App\Services\MailingService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditMailTemplate extends EditRecord
{
    protected static string $resource = MailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Vista previa')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading(fn () => 'Vista previa — ' . $this->record->name)
                ->modalContent(fn () => view(
                    'filament.app.modals.mail-template-preview',
                    ['html' => $this->record->toHtml()]
                ))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar'),

            Action::make('sendTest')
                ->label('Enviar prueba')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    TextInput::make('email_destino')
                        ->label('Email de destino')
                        ->email()
                        ->required()
                        ->default(Auth::user()?->email)
                        ->helperText('Se enviará esta plantilla al email indicado con datos de ejemplo.'),
                ])
                ->action(function (array $data) {
                    $empresa = Filament::getTenant();
                    $service = new MailingService($empresa);

                    if (! $service->isConfigured()) {
                        Notification::make()
                            ->title('Servicio de correo no configurado')
                            ->body('El administrador debe configurar el servicio de correo primero.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $result = $service->sendTemplateTest(
                        to:       $data['email_destino'],
                        template: $this->record
                    );

                    Notification::make()
                        ->title($result['success'] ? 'Correo enviado' : 'Error al enviar')
                        ->body($result['message'])
                        ->{$result['success'] ? 'success' : 'danger'}()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Plantilla guardada';
    }
}
