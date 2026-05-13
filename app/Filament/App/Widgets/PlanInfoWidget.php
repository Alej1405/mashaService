<?php

namespace App\Filament\App\Widgets;

use App\Helpers\PlanHelper;
use Filament\Widgets\Widget;
use Filament\Facades\Filament;

class PlanInfoWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.plan-info';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        $plan    = PlanHelper::current();
        $empresa = Filament::getTenant();

        $features = \App\Models\ServicePlan::where('key', $plan)->value('caracteristicas') ?? [];

        $badgeColor = match ($plan) {
            'basic'      => '#64748b',
            'pro'        => '#2563eb',
            'enterprise' => '#d97706',
            default      => '#64748b',
        };

        $badgeBg = match ($plan) {
            'basic'      => '#f1f5f9',
            'pro'        => '#eff6ff',
            'enterprise' => '#fffbeb',
            default      => '#f1f5f9',
        };

        return [
            'plan'       => $plan,
            'planLabel'  => PlanHelper::label($plan),
            'features'   => $features,
            'badgeColor' => $badgeColor,
            'badgeBg'    => $badgeBg,
        ];
    }
}
