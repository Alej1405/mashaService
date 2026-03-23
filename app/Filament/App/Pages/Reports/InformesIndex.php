<?php

namespace App\Filament\App\Pages\Reports;

use Filament\Pages\Page;

class InformesIndex extends Page
{
    protected static ?string $navigationGroup = 'Contabilidad General';
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Informes';
    protected static ?string $title           = 'Informes Contables';
    protected static string  $view            = 'filament.app.pages.reports.informes-index';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }
}
