<?php

namespace App\Traits;

use Livewire\Attributes\On;

trait HasDashboardPeriodo
{
    public string $periodo;

    public function mount(): void
    {
        $this->periodo = session('dashboard_periodo', 'mes');
    }

    #[On('dashboard-periodo-updated')]
    public function updatePeriodo(string $periodo): void
    {
        $this->periodo = $periodo;
    }

    protected function getFechas(string $p): array
    {
        return match($p) {
            'hoy'       => [today(), today()],
            'semana'    => [now()->startOfWeek(), now()->endOfWeek()],
            'trimestre' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'año'       => [now()->startOfYear(), now()->endOfYear()],
            default     => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    public static function canView(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }
}
