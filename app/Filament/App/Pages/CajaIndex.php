<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class CajaIndex extends Page
{
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Caja';
    protected static ?string $title           = 'Caja';
    protected static string  $view            = 'filament.app.pages.caja-index';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('tesoreria');
    }
}
