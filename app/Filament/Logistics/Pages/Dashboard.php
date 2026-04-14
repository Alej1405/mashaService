<?php

namespace App\Filament\Logistics\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $title           = 'Inicio';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?int    $navigationSort  = -2;

    public function getColumns(): int|string|array
    {
        return ['default' => 1, 'sm' => 2, 'lg' => 3];
    }
}
