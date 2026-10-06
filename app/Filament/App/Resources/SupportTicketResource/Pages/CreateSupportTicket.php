<?php

namespace App\Filament\App\Resources\SupportTicketResource\Pages;

use App\Filament\App\Resources\SupportTicketResource;
use App\Models\SupportTicketMensaje;
use App\Modules\Soporte\Actions\AbrirTicket;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(AbrirTicket::class)->handle(
            auth()->user(), Filament::getTenant()->id, $data['asunto'], $data['descripcion'], $data['prioridad'], SupportTicketMensaje::PANEL, $data['adjuntos'] ?? [],
        );
    }
}
