<?php

namespace App\Filament\Pages\Admin;

use App\Models\Empresa;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ServiciosDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Servicios';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.admin.servicios-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getStats(): array
    {
        $total    = Empresa::count();
        $activas  = Empresa::where('activo', true)->count();
        $inactivas = $total - $activas;

        $porPlan = Empresa::where('activo', true)
            ->selectRaw('plan, COUNT(*) as total')
            ->groupBy('plan')
            ->pluck('total', 'plan')
            ->toArray();

        $threshold    = now()->subMinutes(5)->timestamp;
        $onlineAhora  = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $threshold)
            ->distinct('user_id')
            ->count('user_id');

        $nuevasEsteMes = Empresa::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return compact('total', 'activas', 'inactivas', 'porPlan', 'onlineAhora', 'nuevasEsteMes');
    }

    public function getEmpresasConActividad()
    {
        $threshold = now()->subMinutes(5)->timestamp;

        return Empresa::where('activo', true)
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(function ($empresa) use ($threshold) {
                $userIds = $empresa->users()->pluck('id');
                $empresa->online = DB::table('sessions')
                    ->whereIn('user_id', $userIds)
                    ->where('last_activity', '>=', $threshold)
                    ->count();
                $empresa->ultimo_login = $empresa->users()
                    ->whereNotNull('last_login_at')
                    ->max('last_login_at');
                return $empresa;
            })
            ->sortByDesc('online');
    }
}
