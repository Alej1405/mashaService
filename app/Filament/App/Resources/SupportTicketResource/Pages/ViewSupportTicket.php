<?php

namespace App\Filament\App\Resources\SupportTicketResource\Pages;

use App\Filament\App\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMensaje;
use App\Modules\Soporte\Actions\RegistrarMensajeTicket;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('responder')
                ->label('Responder')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->modalHeading('Responder al ticket')
                ->modalSubmitActionLabel('Enviar')
                ->visible(fn () => $this->record->admiteMensajes())
                ->form([
                    Forms\Components\Textarea::make('mensaje')
                        ->label('Mensaje')
                        ->rows(4)
                        ->requiredWithout('archivo'),
                    Forms\Components\FileUpload::make('archivo')
                        ->label('Imagen o archivo (opcional)')
                        ->maxSize(SupportTicket::MAX_ADJUNTO_KB)
                        ->storeFiles(false)
                        ->helperText('Hasta 20 MB. Llega también por Telegram.'),
                ])
                ->action(function (array $data) {
                    app(RegistrarMensajeTicket::class)->handle(
                        $this->record,
                        auth()->user(),
                        SupportTicketMensaje::PANEL,
                        $data['mensaje'] ?? null,
                        $data['archivo'] ?? null,
                    );

                    Notification::make()->title('Respuesta enviada')->success()->send();
                    $this->redirect(SupportTicketResource::getUrl('index'));
                }),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasRole(['admin_empresa', 'super_admin'])),
        ];
    }
}
