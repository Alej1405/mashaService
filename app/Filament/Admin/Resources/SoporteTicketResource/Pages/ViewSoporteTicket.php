<?php

namespace App\Filament\Admin\Resources\SoporteTicketResource\Pages;

use App\Filament\Admin\Resources\SoporteTicketResource;
use App\Filament\App\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSoporteTicket extends ViewRecord
{
    protected static string $resource = SoporteTicketResource::class;

    public function getTitle(): string
    {
        return 'Ticket #'.$this->record->id.' · '.$this->record->empresa?->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            SupportTicketResource::accionResponder(SoporteTicketResource::getUrl('index')),

            Actions\Action::make('estado')
                ->label('Cambiar estado')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options(SupportTicket::opcionesEstado())
                        ->default(fn () => $this->record->status)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['status' => $data['status']]);
                    Notification::make()->title('Estado: '.$this->record->statusLabel())->success()->send();
                    $this->redirect(SoporteTicketResource::getUrl('index'));
                }),
        ];
    }
}
