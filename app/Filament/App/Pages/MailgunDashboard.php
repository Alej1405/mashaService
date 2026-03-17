<?php

namespace App\Filament\App\Pages;

use App\Helpers\PlanHelper;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class MailgunDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Mailing';
    protected static ?string $navigationGroup = 'Mailing';
    protected static ?string $title           = 'Dashboard de Mailing';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.app.pages.mailgun-dashboard';

    /** Accesible a todos los planes. */
    public static function canAccess(): bool
    {
        return true;
    }

    public function getViewData(): array
    {
        $empresa      = Filament::getTenant();
        $configurado  = ! empty($empresa->mailgun_api_key) && ! empty($empresa->mailgun_domain);
        $plan         = PlanHelper::current();

        return [
            'empresa'     => $empresa,
            'configurado' => $configurado,
            'plan'        => $plan,
            'planLabel'   => PlanHelper::label($plan),
        ];
    }
}
