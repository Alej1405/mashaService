<?php

namespace App\Filament\Basic\Pages;

use App\Models\MailTemplate;
use App\Services\MailingService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $title           = 'Dashboard';
    protected static ?int    $navigationSort  = -2;
    protected static string  $view            = 'filament.basic.pages.dashboard';

    public function getViewData(): array
    {
        $empresa  = Filament::getTenant();
        $user     = auth()->user();
        $service  = new MailingService($empresa);
        $hora     = now()->hour;

        Carbon::setLocale('es');

        $saludo = match (true) {
            $hora >= 6 && $hora < 12  => 'Buenos días',
            $hora >= 12 && $hora < 19 => 'Buenas tardes',
            default                    => 'Buenas noches',
        };

        $configurado  = $service->isConfigured();
        $stats        = $configurado ? $service->getStats(30) : [];
        $events       = $configurado ? $service->getEvents(8)  : [];
        $plantillas   = MailTemplate::count();

        // Actividad reciente (últimos 7 días)
        $stats7       = $configurado ? $service->getStats(7) : [];

        return [
            'saludo'       => $saludo . ', ' . explode(' ', $user->name)[0],
            'fecha'        => ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')),
            'empresa'      => $empresa,
            'user'         => $user,
            'configurado'  => $configurado,
            'stats'        => $stats,
            'stats7'       => $stats7,
            'events'       => $events,
            'plantillas'   => $plantillas,
        ];
    }
}
