<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class DashboardHeaderWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.dashboard-header';
    
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public string $periodo;

    public function mount()
    {
        $this->periodo = session('dashboard_periodo', 'mes');
    }

    public function updatedPeriodo($value)
    {
        session(['dashboard_periodo' => $value]);
        $this->dispatch('dashboard-periodo-updated', periodo: $value);
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $hora = now()->hour;
        
        $saludo = match(true) {
            $hora >= 6 && $hora < 12 => 'Buenos días',
            $hora >= 12 && $hora < 19 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        Carbon::setLocale('es');
        $fechaLarga = now()->translatedFormat('l, d \d\e F \d\e Y');

        return [
            'saludo' => $saludo . ', ' . $user->name,
            'fechaActual' => ucfirst($fechaLarga),
            'tenant' => \Filament\Facades\Filament::getTenant()->slug,
        ];
    }
}
