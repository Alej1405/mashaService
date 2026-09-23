<?php

namespace App\Notifications;

use App\Models\Declaracion;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * El aviso en la campana del panel cuando la declaración terminó.
 */
class DeclaracionLista extends Notification
{
    use Queueable;

    public function __construct(private readonly Declaracion $declaracion)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $tipo = Declaracion::TIPOS[$this->declaracion->tipo] ?? $this->declaracion->tipo;
        $listo = $this->declaracion->estado === 'listo';

        return FilamentNotification::make()
            ->title($listo ? "{$tipo} de {$this->declaracion->periodo} listo" : "Falló {$tipo} de {$this->declaracion->periodo}")
            ->body($listo
                ? 'Ya puedes descargarlo desde Declaraciones.'
                : ($this->declaracion->mensaje ?? 'Revisa el detalle en Declaraciones.'))
            ->icon($listo ? 'heroicon-o-document-check' : 'heroicon-o-exclamation-triangle')
            ->status($listo ? 'success' : 'danger')
            ->getDatabaseMessage();
    }
}
