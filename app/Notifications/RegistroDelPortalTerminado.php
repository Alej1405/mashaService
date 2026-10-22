<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * El aviso en la campana cuando terminó de registrar las facturas del portal.
 */
class RegistroDelPortalTerminado extends Notification
{
    use Queueable;

    public function __construct(private readonly array $cuenta)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $c = $this->cuenta;

        return FilamentNotification::make()
            ->title(($c['registradas'] + $c['pendientes']) . ' facturas del portal con su detalle')
            ->body(collect([
                $c['registradas'] ? "{$c['registradas']} quedaron registradas con su asiento" : null,
                $c['pendientes'] ? "{$c['pendientes']} están en «Listas para registrar»: elige cómo se pagaron" : null,
                $c['manuales'] ? "{$c['manuales']} ya no las devuelve el SRI: están en «Falta el detalle», sube su XML" : null,
                $c['errores'] ? "{$c['errores']} no se pudieron: " . implode(' · ', array_slice($c['detalle_errores'], 0, 2)) : null,
            ])->filter()->implode('. ') ?: 'No había facturas por traer.')
            ->icon('heroicon-o-inbox-stack')
            ->status($c['errores'] ? 'warning' : 'success')
            ->getDatabaseMessage();
    }
}
